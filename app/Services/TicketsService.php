<?php

namespace App\Services;


class TicketsService extends AbstractService
{
    public function listAllTickets()
    {
        $tickets = $this->client->get('tickets', []);

        return $tickets;
    }

    public function getTransaction($trans_id)
    {
        $trans = $this->client->get('orders/'.$trans_id, []);

        /*
        $items = array();
        foreach ($trans['items'] as $item) {
            $items[] = [
                'item_id' => $item['item_id'],
                'description' => $item['description'],
                'qty_ordered' => $item['qty_ordered'],
                'price_total' => $item['price_total'],
                'lot_id' => !empty($item['lot_id']) ? implode(",",$item['lot_id']) : '',
                'salesperson_id' => !empty($item['custom_data']['salesperson_id']) ? implode(",",$item['custom_data']['salesperson_id']) : '',
                'is_pwp' => $item['parent_id'] > 0 ? 'PWP' : '',
                'is_demo' => $item['custom_data']['is_demo'] ? 'DEMO' : ''
            ];
        }
        $transaction = [
            'order_num' => $trans['order_num'],
            'order_type' => $trans['order_type'],
            'cashier_id' => $trans['cashier_id'],
            'created_on' => $trans['created_on'],
            'loc_id' => $trans['loc_id'],
            'pos_id' => $trans['pos_id'],
            'customer_id' => $trans['customer_id'],
            'customer_name' => $trans['customer_name'],
            'customer_phone' => $trans['customer_phone'],
            'customer_email' => $trans['customer_email'],
            'total_savings' => $trans['total_savings'],
            'points_earned' => $trans['points_earned'],
            'total_amount' => $trans['total_amount'],
            'tax_amount' => $trans['tax_amount'],
            'items' => $items,
            'transactions' => $transactions
        ];
        */

        $trans['order_status'] = 'Paid';
        if ($trans['order_status_level']==0)
            $trans['order_status'] = 'Suspended';
        else if ($trans['order_status_level']==-1)
            $trans['order_status'] = 'Voided';

        $transactions = array();
        foreach ($trans['transactions'] as $t) {
            if ($t['pay_mode'] != 'CASH' && $t['pay_mode'] != 'CASH_CHANGE_DUE' && $t['pay_mode'] != 'CASH_ROUNDING_ADJ' && $t['trans_amount'] != 0){
                $transactions[] = [
                    'pay_mode' => ($t['pay_mode'] == 'CASH_PAID') ? 'CASH' : $t['pay_mode'] .' '. $t['pay_type'],
                    'trans_desc' => trim($t['masked_card_number'] . ' ' . $t['approval_code'] . ' ' . $t['transaction_reference_id']),
                    'trans_amount' => $t['trans_amount']
                ];
            }
        }
        $trans['transactions_display'] = $transactions;

        for ($i=0; $i<count($trans['items']); $i++) {
            $item = $trans['items'][$i];

            $trans['items'][$i]['regular_price'] = floatval(str_replace(',','',$item['regular_price']));
            $trans['items'][$i]['unit_price'] = floatval(str_replace(',','',$item['unit_price']));
            $trans['items'][$i]['unit_discount'] = floatval(str_replace(',','',$item['unit_discount']));
            $trans['items'][$i]['qty_ordered'] = floatval(str_replace(',','',$item['qty_ordered']));
            $trans['items'][$i]['qty_refunded'] = floatval(str_replace(',','',$item['qty_refunded']));
            $trans['items'][$i]['price_total'] = floatval(str_replace(',','',$item['price_total']));
            $trans['items'][$i]['discount_total'] = floatval(str_replace(',','',$item['discount_total']));

            $trans['items'][$i]['lot_id'] = !empty($item['lot_id']) ? $item['lot_id'] : [];
            $trans['items'][$i]['salesperson_id'] = !empty($item['custom_data']['salesperson_id']) ? $item['custom_data']['salesperson_id'] : [];
            $trans['items'][$i]['is_pwp'] = $item['parent_id'] > 0 ? 'PWP' : '';
            $trans['items'][$i]['is_demo'] = (isset($item['custom_data']['is_demo']) && $item['custom_data']['is_demo']) ? 'DEMO' : '';

            $trans['items'][$i]['show_price_edited'] = '';
            if (isset($item['custom_data']['price_edited']) && $item['custom_data']['price_edited']==1
                && $trans['order_status_level']>=1 && $trans['order_type']=='PS'
            ) {
                $trans['items'][$i]['show_price_edited'] = 'Price overwrite';
                if (isset($item['custom_data']['price_edited_approval']) && $item['custom_data']['price_edited_approval']!='')
                    $trans['items'][$i]['show_price_edited'] .= ' by ' . $item['custom_data']['price_edited_approval'];
            }
        }
        return $trans;
    }

    public function getTransReceipt($trans_id)
    {
        $receipt = $this->client->get('orders/'.$trans_id.'/reprint_receipt', []);
        return $receipt;
    }

    public function getDeposits($loc_id, $date_from, $date_to)
    {
        $loc_id = isset($loc_id) ? $loc_id : '';
        $date_from = isset($date_from) ? $date_from : '';
        $date_to = isset($date_to) ? $date_to : '';
        $coy_id = 'CTL';

        $url = 'coy_id=' . $coy_id .
            '&loc_id=' . $loc_id .
            '&date_from=' . $date_from .
            '&date_to=' . $date_to;
        $deposits = $this->client->get('api/deposit?'.$url, []);

        return (object)$deposits['data'];
    }

    public function syncTrans($trans_id)
    {
        $receipt = $this->client->get('api/report/pos_cherps_single/'.$trans_id, []);
        return $receipt;
    }

}

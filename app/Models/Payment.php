<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
class Payment extends Model
{

  
   
  public static  function get_transactions($user_id = '', $offset = '0', $limit = '25')
    {
        $where =  [];
        $count_sql = Payment::selectRaw('COUNT(id) as `total`');
        if (!empty($user_id)) {
            $where['customer_id'] = $user_id;
        }

        if (isset($where) && !empty($where)) {
            $count_sql->where($where);
        }
        $count = $count_sql->get()->toArray();
        $total = $count[0]['total'];

        /* query for transactions list */
        $transactions_sql = Payment::selectRaw('customer_id as user_id,currency as currency_code,order_id,amount,description as message,status,payment_type as transaction_type,payment_channel as type,charge_id as txn_id,updated_at as transaction_date,created_at as date_created');
        if (isset($where) && !empty($where)) {
            $transactions_sql->where($where);
        }
        
        $transactions_sql->orderBy('id','DESC');

        if ($limit != '' && $offset !== '') {
            $transactions_sql->limit($limit)->offset($offset);
        }
        $q = $transactions_sql->get();
        foreach ($q  as $key => $value) {
  
            if($value->type=='cod'){
                $type=Fun::fetch_details(['key' => 'payment_cod_name'], 'settings', 'value');
                $q[$key]->type= isset($type[0]->value)?$type[0]->value:$q[$key]->type;
            }
            if($value->type=='bank_transfer'){
                $type=Fun::fetch_details(['key' => 'payment_bank_transfer_name'], 'settings', 'value');
                $q[$key]->type= isset($type[0]->value)?$type[0]->value:$q[$key]->type;
            }

        }
        $transactions['data'] = $q->toArray();
        $transactions['total'] = $total;
        $transactions;

        return $transactions;
    }
   
}

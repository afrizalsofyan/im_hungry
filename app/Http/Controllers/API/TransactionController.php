<?php

namespace App\Http\Controllers\API;

use Midtrans\Snap;
use Midtrans\Config;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    public function all(Request $request)
    {
        //masukan inputan request dari si mobile ke variabel
        $id = $request->input('id');
        //6 adalah jumlah limit yang akan ditampilkan
        $limit = $request->input('limit', 6);
        $food_id = $request->input('food_id');
        $status = $request->input('status');

        //ambil data transaksinya berdasarkan id
        if ($id) {
            //tambahinr relasi dari si food_id dan user_id yang telah di buat di model
            $transaction = Transaction::with(['food', 'user'])->find($id);
            //cek jika transaksinya berhasi diambil datanya, dan sukses diambil
            if ($transaction) {
                return ResponseFormatter::success($transaction, 'Successfully for getting data transaction');
            } else {
            //cek kalau gagal beri pesan eror
                return ResponseFormatter::error(null, 'Failed for getting data transaction', 404);
            }
        }

        //ambil data transaction si food nya dan statusnya dari relasi yang telah dibuat dimodel
        $transaction = Transaction::with(['food', 'user'])->where('user_id', Auth::user()->id);
        //cek food_idnya
        if ($food_id) {
            //beri query kalo si trassaksi food_id untuk diambil datanya
            $transaction->where('food_id', $food_id);
        }
        if ($status) {
            // cek status yang ada di transction
            $transaction->where('status', $status);
        }
        
        //kembalikan data untuk ditampilkan
        return ResponseFormatter::success($transaction->paginate($limit), 'Successfully getting transaction data transaction');
    }

    public function update(Request $request, $id)
    {
        //ini untuk melakukan update transaksi

        //ambil data yang mau di update
        $transaction = Transaction::findOrFail($id); 

        // update datanya
        $transaction->update($request->all()); 

        //kembalikan datanya
        return ResponseFormatter::success($transaction, 'Transaction has been updated'); 
    }

    public function checkout(Request $request)
    {
        $request->validate([
            'food_id' => 'required|exists:food,id',
            'user_id' => 'required|exists:users,id',
            'quantity'=>'required',
            'total'=>'required',
            'status'=>'required',
        ]);
        
        $transaction = Transaction::created([
            'food_id' =>$request->food_id,
            'user_id' =>$request->user_id,
            'quantity' =>$request->quantity,
            'total' =>$request->total,
            'status' =>$request->status,
            'payment_url' => '',
        ]);

        //configuration midtrans
        Config::$serverKey = config('services.midtrans.serverKey');
        Config::$isProduction = config('services.midtrans.isProduction');
        Config::$isSanitized = config('services.midtrans.isSanitized');
        Config::$is3ds = config('services.midtrans.is3ds');

        //Panggil transaksi yang telah dibuat
        $transaction = Transaction::with(['food', 'user'])->find($transaction->id);

        // membuat transaksi midtrans
        $midtrans = [
            'transaction_details' =>[
                'order_id'=> $transaction-> id,
                'gross_amount' => (int) $transaction->total
            ],
            'customers_details' => [
                'first_name' => $transaction->user->name,
                'email' => $transaction->user->email,
            ],
            'enabled_payments' => [
                'gopay', 'bank_transfer',
            ],
            'vtweb' => []
        ];
        //memanggil midtrans

        try {
            //ambil halaman payment midtrans
            $paymentUrl = Snap::createTransaction($midtrans)->redirect_url;
            $transaction->payment_url = $paymentUrl;
            $transaction->save();

            //mengembalikan data ke API
            return ResponseFormatter::success($transaction, 'Transaction berhasil');
        } catch (Exception $e) {
            return ResponseFormatter::error($e->getMessage(), 'Transaksi gagal');
        }

        
    }
}

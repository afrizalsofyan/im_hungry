<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\Food;
use Illuminate\Http\Request;

class FoodController extends Controller
{
    public function all(Request $request)
    {
        //ambil datanya dari yang dibutuhin sama si mobile
        $id = $request->input('id');
        //6 adalah jumlah limit yang akan ditampilkan
        $limit = $request->input('limit', 6);
        $name = $request->input('name');
        $types = $request->input('types');

        //sorting berdasarkan harga
        $price_from = $request->input('price_from');
        $price_to = $request->input('price_to');

        //sorting berdasarkan rating
        $rate_from = $request->input('rate_from');
        $rate_to = $request->input('rate_to');

        //ambil data berdasarkan id yang diambil
        if ($id) {
            //cari datanya berdasarkan idnya
            $food = Food::find($id);
            //beri pesan saat berhasil diambil datanya
            if ($food) {
                return ResponseFormatter::success($food, 'Successfully for getting data');
            } else {
            //beri pesan saat datanya gagal di ambil
                return ResponseFormatter::error(null, 'Failed for getting data', 404);
            }
        }

        //ambil data berdasarkan limit, name , type, price, dan rate
        $food = Food::query();

        if ($name) {
            //opeartir like akan mencari yang mirip dengan variabel
            $food->where('name', 'like', '%' . $name . '%');
        }
        if ($types) {
            $food->where('type', 'like', '%' . $types . '%');
        }
        if ($price_from) {
            $food->where('price', '>=', $price_from);
        }
        if ($price_to) {
            $food->where('price', '<=', $price_to);
        }
        if ($rate_from) {
            $food->where('rate', '>=', $rate_from);
        }
        if ($rate_to) {
            $food->where('rate', '<=', $rate_to);
        }
        //kembalikan data untuk ditampilkan
        return ResponseFormatter::success($food->paginate($limit), 'Successfully getting food data');
    }
}

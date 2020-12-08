<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class eToken extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('etoken')->insert([
            'token' => 'VanMin-etoken--'.md5(Hash::make('oanhngungoc').Carbon::now()->timestamp).'/Hash/'.Carbon::now('Asia/Ho_Chi_Minh')->timestamp,
            'time_expire' => Carbon::now('Asia/Ho_Chi_Minh')->addMonths(1),
        ]);
    }
}

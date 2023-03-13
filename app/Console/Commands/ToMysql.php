<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ToMysql extends Command
{
    /**
     * 命令名称及签名
     *
     * @var string
     */
    protected $signature = 'tomysql';

    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = '将省市区数据存放到表中';

    public function handle()
    {
        $json = Storage::get('pca-code.json');
        $data = json_decode($json, true);
        $flat_data = self::flatten($data);
        foreach ($flat_data as &$value) {
            $value['merge_name'] = trim($value['merge_name'], ',');
            $value['province_code'] = substr($value['code'], 0, 2);
            if ($value['level'] > 1) {
                $value['city_code'] = substr($value['code'], 0, 4);
                if ($value['level'] > 2) {
                    $value['area_code'] = $value['code'];
                }
            }
            DB::table('areas')->insert($value);
        }
        Storage::put('data.json', json_encode($flat_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }

    private static function flatten(&$data, $level = 1, $merge_name = ''): array
    {
        $result = [];
        foreach ($data as &$item) {
            $item['level'] = $level;
            $item['merge_name'] = $merge_name . $item['name'] . ',';
            $children = $item['children'] ?? [];
            unset($item['children']);
            $result[] = $item;
            if (!empty($children)) {
                $result = array_merge($result, self::flatten($children, $level + 1, $item['merge_name']));
            }
        }
        return $result;
    }
}

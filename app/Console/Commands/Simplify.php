<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class Simplify extends Command
{
    const ethnicGroups = [
        //56个民族
        '汉族', '蒙古族', '回族', '藏族', '维吾尔族', '苗族', '彝族', '壮族', '布依族', '朝鲜族', '满族', '侗族', '瑶族', '白族', '土家族', '哈尼族',
        '哈萨克族', '傣族', '黎族', '傈僳族', '佤族', '畲族', '高山族', '拉祜族', '水族', '东乡族', '纳西族', '景颇族', '柯尔克孜族', '土族', '达斡尔族',
        '仫佬族', '羌族', '布朗族', '撒拉族', '毛南族', '仡佬族', '锡伯族', '阿昌族', '普米族', '塔吉克族', '怒族', '乌孜别克族', '俄罗斯族', '鄂温克族',
        '德昂族', '保安族', '裕固族', '京族', '塔塔尔族', '独龙族', '鄂伦春族', '赫哲族', '门巴族', '珞巴族', '基诺族',
        //其他
        '各族'
    ];
    /**
     * 命令名称及签名
     *
     * @var string
     */
    protected $signature = 'simplify';
    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = '将省市区数据进行简化';

    public function handle()
    {
        $areas = DB::table('areas')
            ->orderBy('level')  //必须按照level升序排列,确保short_merge_name生成正确
            ->orderBy('id')
            ->get();
        foreach ($areas as $row) {
            $short_name = self::simplify($row->name, $row->level);
            // if ($short_name != $row->name) {
            //     echo $row->name . ' -> ' . $short_name . PHP_EOL;
            // }
            if ($row->level == 1) {
                $sql = "UPDATE `areas` SET `short_merge_name`='$short_name' WHERE `province_code`={$row->code}";
                DB::update($sql);
            } elseif ($row->level == 2) {
                $sql = "UPDATE `areas` SET `short_merge_name`=CONCAT(`short_merge_name`,',','$short_name') WHERE `city_code`={$row->code}";
                DB::update($sql);
            }
            if ($row->level == 3) {
                DB::update("UPDATE `areas` SET `short_name`='$short_name',`short_merge_name`=CONCAT(`short_merge_name`,',','$short_name') WHERE `id`={$row->id}");
            } else {
                DB::update("UPDATE `areas` SET `short_name`='$short_name' WHERE `id`={$row->id}");
            }
        }
    }

    private static function simplify($raw_name, $level)
    {
        if ($level == 3) {
            switch ($raw_name) {
                case '郑州航空港经济综合实验区':
                    return '郑州空港经济区';
                case '吉林中国新加坡食品区':
                    return '吉林食品区';
                case '石家庄循环化工园区':
                    return '石家庄化工园区';
            }
            if (strpos($raw_name, '管理区') !== false) {
                return preg_replace('/.*?市/', '', $raw_name);
            }
            if (strpos($raw_name, '高新') !== false) {
                return preg_replace('/高新.*/', '高新区', $raw_name);
            }
            if (strpos($raw_name, '经济') !== false) {
                return preg_replace('/经济.*/', '经开区', $raw_name);
            }
            if (strpos($raw_name, '高技术') !== false) {
                return preg_replace('/高技术.*/', '高新区', $raw_name);
            }
            if (strpos($raw_name, '园区') !== false) {
                return str_replace('现代', '', $raw_name);
            }
            if (strpos($raw_name, '城乡一体化') !== false) {
                return str_replace('城乡一体化', '', $raw_name);
            }
            if (strpos($raw_name, '转型综合改革示范区')) {
                return str_replace('转型综合改革示范区', '综改区', $raw_name);
            }
            if (strpos($raw_name, '文化旅游创意园区')) {
                return str_replace('文化旅游创意园区', '文创园', $raw_name);
            }
        }

        $name1 = str_replace('自治', '', $raw_name);
        $name = self::ethnicReject($name1);

        if ($level == 1) {
            $name = str_replace('维吾尔', '', $name);
            $name = mb_substr($name, 0, -1);
        } elseif ($level == 2) {
            foreach (['蒙古', '哈萨克', '柯尔克孜'] as $extra) {
                $name = str_replace($extra, '', $name);
            }
            switch ($name) {
                case '区直辖县级行政区划':
                case '省直辖县级行政区划':
                    return '直辖县';
            }
        } elseif ($level == 3) {
            if (strpos($name, '内蒙古') === false) {
                foreach (['塔吉克', '锡伯', '蒙古', '哈萨克'] as $extra) {
                    $name = str_replace($extra, '', $name);
                }
            }
            if (mb_strlen($name) <= 1) {
                $name = str_replace('族', '', $name1);
            }
        }
        return $name;
    }

    private static function ethnicReject($name, $times = 0)
    {
        if ($times > 5) {
            return $name;
        }
        if (strpos($name, '族') !== false) {
            foreach (self::ethnicGroups as $value) {
                $name = str_replace($value, '', $name);
            }
            return self::ethnicReject($name, $times + 1);
        } else {
            return $name;
        }
    }

}

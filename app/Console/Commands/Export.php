<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class Export extends Command
{
    /**
     * 命令名称及签名
     * value表示取数据表中哪个字段作为value 默认code字段 可选id等
     * label表示取数据表中哪个字段作为label 默认short_name字段 可选name等
     * @var string
     */
    protected $signature = 'export {provinces=0} {value=code} {label=short_name}';
    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = '将mysql中数据导出为json(供前端使用等)';

    public function handle()
    {
        if (file_exists('areas.json')) {
            $path = realpath('areas.json');
            echo '<' . $path . '>已存在,请删除或移动后在执行此方法' . PHP_EOL;
            exit;
        }

        $provinces = $this->argument('provinces');
        $value = $this->argument('value');
        $label = $this->argument('label');
        echo "正在导出为json文件，参数provinces：{$provinces}，参数value：{$value}，参数label：{$label}" . PHP_EOL;
        $builder = DB::table('areas')
            ->orderBy('level')  //必须按照level升序排列,确保short_merge_name生成正确
            ->orderBy('id');
        if (!empty($provinces)) {
            $builder->whereIn('province_code', explode(',', $provinces));
        }
        $areas = $builder->get();
        $all_provinces = $areas->where('level', 1);
        $all_cities = $areas->where('level', 2);
        $all_areas = $areas->where('level', 3);
        $tree = [];
        foreach ($all_provinces as $pc) {
            $tree[$pc->code] = [
                'value' => $pc->$value,
                'label' => $pc->$label,
                'children' => []
            ];
            $_cities = $all_cities->where('province_code', $pc->code);
            $_areas_by_c = $all_areas->where('province_code', $pc->code);
            foreach ($_cities as $ct) {
                $tree[$pc->code]['children'][$ct->code] = [
                    'value' => $ct->$value,
                    'label' => $ct->$label,
                    'children' => []
                ];
                $_areas = $_areas_by_c->where('city_code', $ct->code);
                foreach ($_areas as $area) {
                    $tree[$pc->code]['children'][$ct->code]['children'][$area->code] = [
                        'value' => $area->$value,
                        'label' => $area->$label,
                        // 'children' => []
                    ];
                }
                $tree[$pc->code]['children'][$ct->code]['children'] = array_values($tree[$pc->code]['children'][$ct->code]['children']); //去掉键
            }
            $tree[$pc->code]['children'] = array_values($tree[$pc->code]['children']); //去掉键
        }
        $tree = array_values($tree); //去掉键

        file_put_contents('areas.json', json_encode($tree, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        $path = realpath('areas.json');
        echo '已成功保存至<' . $path . '>';
        exit;
    }

}

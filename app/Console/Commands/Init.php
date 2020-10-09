<?php

namespace App\Console\Commands;

use Aliyun\OTS\OTSServerException;
use Aliyun\OTS\Consts\PrimaryKeyTypeConst;
use Aliyun\OTS\Consts\DefinedColumnTypeConst;
use Aliyun\OTS\Consts\SortOrderConst;
use Aliyun\OTS\Consts\FieldTypeConst;
use Illuminate\Console\Command;

class Init extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'init';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '初始化';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('正在初始化');

        $client = get_ots_client();

        $table_name = config('config.table_name');

        try {
            $result = $client->describeTable([
                'table_name' => $table_name,
            ]);

            if(isset($result['table_meta'])) {
                return true;
            }
        } catch (OTSServerException $e) {
            if($e->getOTSErrorCode() === 'OTSObjectNotExist') {
                $result = $client->createTable([
                    'table_meta' => [
                        'table_name'            => $table_name,
                        'primary_key_schema'    => [
                            ['id', PrimaryKeyTypeConst::CONST_INTEGER],
                        ],
                        'defined_column'        => [
                            ['url', DefinedColumnTypeConst::DCT_STRING],
                            ['create_time', DefinedColumnTypeConst::DCT_STRING],
                        ],
                    ],
                    'reserved_throughput' => [
                        'capacity_unit' => [
                            'read'  => 0,
                            'write' => 0,
                        ],
                    ],
                    'table_options' => [
                        'time_to_live'                  => -1,
                        'max_versions'                  => 1,
                        'deviation_cell_version_in_sec' => 86400,
                    ],
                ]);

                $result = $client->createSearchIndex([
                    'table_name'    => $table_name,
                    'index_name'    => $table_name . '_url_index',
                    'schema' => [
                        'field_schemas' => [
                            [
                                'field_name'            => 'id',
                                'field_type'            => FieldTypeConst::LONG,
                                'is_array'              => false,
                                'index'                 => false,
                                'analyzer'              => false,
                                'enable_sort_and_agg'   => true,
                                'store'                 => true,
                            ],
                            [
                                'field_name'            => 'url',
                                'field_type'            => FieldTypeConst::KEYWORD,
                                'is_array'              => false,
                                'index'                 => true,
                                'analyzer'              => false,
                                'enable_sort_and_agg'   => false,
                                'store'                 => true,
                            ],
                        ],
                        'index_sort' => [
                            [
                                'field_sort' => [
                                    'field_name'    => 'id',
                                    'order'         => SortOrderConst::SORT_ORDER_DESC,
                                ],
                            ],
                            [
                                'pk_sort' => [
                                    'order' => SortOrderConst::SORT_ORDER_DESC,
                                ],
                            ],
                        ],
                    ],
                ]);
            }
        }

        return 0;
    }
}

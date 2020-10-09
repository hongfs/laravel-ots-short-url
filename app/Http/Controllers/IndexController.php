<?php

namespace App\Http\Controllers;

use Tuupola\Base62;
use Aliyun\OTS\OTSServerException;
use Aliyun\OTS\Consts\DirectionConst;
use Aliyun\OTS\Consts\ReturnTypeConst;
use Aliyun\OTS\Consts\ComparatorTypeConst;
use Aliyun\OTS\Consts\RowExistenceExpectationConst;
use Aliyun\OTS\Consts\SortOrderConst;
use Aliyun\OTS\Consts\QueryTypeConst;
use Aliyun\OTS\Consts\ColumnReturnTypeConst;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class IndexController extends Controller
{
    /**
     * OTS Client
     *
     * @return \Aliyun\OTS\OTSClient
     */
    protected $client;

    /**
     * Base62
     *
     * @return \Tuupola\Base62
     */
    protected $base62;

    /**
     * 数据表名称
     *
     * @return string
     */
    protected $table_name;

    /**
     * URL 是否已存在，不存在返回 false, 存在返回 ID
     *
     * @param string  $url  URL
     * @return int|false
     * @throws \Aliyun\OTS\OTSServerException
     * @throws \Aliyun\OTS\OTSClientException
     */
    protected function has_url(string $url)
    {
        $result = $this->client->search([
            'table_name'    => $this->table_name,
            'index_name'    => $this->table_name . '_url_index',
            'search_query'  => [
                'offset'            => 0,
                'limit'             => 1,
                'get_total_count'   => true,
                'query'             => [
                    'query_type'    => QueryTypeConst::TERM_QUERY,
                    'query'         => [
                        'field_name'    => 'url',
                        'term'          => $url,
                    ],
                ],
                'sort' => [
                    [
                        'field_sort' => [
                            'field_name'    => 'id',
                            'order'         => SortOrderConst::SORT_ORDER_DESC,
                        ],
                    ],
                ],
            ],
            'columns_to_get' => [
                'return_type' => ColumnReturnTypeConst::RETURN_ALL,
            ],
        ]);

        if(!count($result['rows'])) {
            return false;
        }

        $row = collect($result['rows'][0]['primary_key'])->pluck(1, 0)->toArray();

        if(!isset($row['id'])) {
            return false;
        }

        return $row['id'];
    }

    /**
     * 生成 ID
     *
     * @return int
     * @throws \Aliyun\OTS\OTSServerException
     * @throws \Aliyun\OTS\OTSClientException
     */
    protected function generate_id()
    {
        $result = $this->client->getRange([
            'table_name'                    => $this->table_name,
            'inclusive_start_primary_key'   => [
                ['id', PHP_INT_MAX],
            ],
            'exclusive_end_primary_key'     => [
                ['id', 0],
            ],
            'direction'                     => DirectionConst::CONST_BACKWARD,
            'limit'                         => 1,
            'max_versions'                  => 1,
            'start_column'                  => 'id',
        ]);

        if(!count($result['rows'])) {
            return config('config.start_id_value');
        }

        $row = $result['rows'][0];
        $row = array_merge($row['primary_key'], $row['attribute_columns']);
        $row = collect($row)->pluck(1, 0)->toArray();

        $inc = random_int(config('config.inc_min_value', 1), config('config.inc_max_value'));

        return (int) bcadd($row['id'], $inc, 0);
    }

    /**
     * 生成访问 URL
     *
     * @param int  $id  ID
     * @return string
     */
    protected function generate_url(int $id)
    {
        return action('IndexController@show', ['name' => $this->base62->encodeInteger($id)]);
    }

    public function __construct()
    {
        $this->client       = get_ots_client();
        $this->base62       = new Base62;
        $this->table_name   = config('config.table_name');
    }

    /**
     * 创建视图
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('index');
    }

    /**
     * 创建处理
     *
     * @param \Illuminate\Http\Request  $request
     * @param int  $retry  重试次数
     * @return \Illuminate\Http\Response
     * @throws \Aliyun\OTS\OTSServerException
     * @throws \Aliyun\OTS\OTSClientException
     */
    public function store(Request $request, int $retry = 10)
    {
        $input = $request->only('url');

        $validator = Validator::make($input, [
            'url'   => 'required|url',
        ]);

        $validator->setAttributeNames([
            'url'   => '链接',
        ]);

        if($validator->fails()) {
            return error((string) $validator->errors()->first());
        }

        $url = $input['url'];

        if(($id = $this->has_url($url)) !== false) {
            return result([
                'url' => $this->generate_url($id),
            ]);
        }

        $parse_url = parse_url(strtolower($url));

        if(!in_array($parse_url['scheme'], ['http', 'https'])) {
            return error('不支持当前协议');
        }

        $id = $this->generate_id();

        try {
            $result = $this->client->putRow([
                'table_name'    => $this->table_name,
                'condition'     => [
                    'row_existence'     => RowExistenceExpectationConst::CONST_EXPECT_NOT_EXIST,
                    'column_condition'  => [
                        'column_name'   => 'id',
                        'value'         => $id,
                        'comparator'    => ComparatorTypeConst::CONST_EQUAL,
                    ],
                ],
                'primary_key'   => [
                    ['id', $id],
                ],
                'attribute_columns' => [
                    ['url', $url],
                    ['create_time', date('Y-m-d H:i:s')],
                ],
                'return_content' => [
                    'return_type' => ReturnTypeConst::CONST_PK,
                ],
            ]);
        } catch (OTSServerException $e) {
            if($e->getOTSErrorCode() === 'OTSConditionCheckFail') {
                if(!$retry) {
                    return error('检查失败');
                }

                return $this->store($request, $retry--);
            }

            throw $e;
        }

        return result([
            'url' => $this->generate_url($id),
        ]);
    }

    /**
     * 访问跳转
     *
     * @param string|int $name 经过 Base62 的 ID
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse|void
     * @throws \Aliyun\OTS\OTSServerException
     * @throws \Aliyun\OTS\OTSClientException
     */
    public function show($name)
    {
        $id = $this->base62->decodeInteger($name);

        $result = $this->client->getRow([
            'table_name'    => $this->table_name,
            'primary_key'   => [
                ['id', $id],
            ],
            'max_versions'  => 1,
        ]);

        if(!count($result['primary_key'])) {
            // 数据不存在
            return abort(404);
        }

        $row = collect($result['attribute_columns'])->pluck(1, 0)->toArray();

        if(!isset($row['url'])) {
            // 不存在跳转数据
            return abort(404);
        }

        return redirect()->away($row['url']);
    }
}

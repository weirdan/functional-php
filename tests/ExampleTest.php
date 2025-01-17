<?php

namespace Tests\Functional;

use Basko\Functional as f;

class ExampleTest extends BaseTest
{
    public static function products()
    {
        return [
            [
                'description' => 't-shirt',
                'qty' => 2,
                'value' => 20
            ],
            [
                'description' => 'jeans',
                'qty' => 1,
                'value' => 30
            ],
            [
                'description' => 'boots',
                'qty' => 1,
                'value' => 40
            ],
        ];
    }

    public function test_products()
    {
        $imperativeTotalQty = 0;
        foreach (static::products() as $product) {
            $imperativeTotalQty += $product['qty'];
        }

        $imperativeAmount = 0;
        foreach (static::products() as $product) {
            $imperativeAmount += $product['qty'] * $product['value'];
        }

        $totalQty = f\compose(f\sum, f\pluck('qty'));
        $pipedTotalQty = f\pipe(f\pluck('qty'), f\sum);
        $amount = f\compose(f\sum, f\map(f\compose(f\product, f\props(['value', 'qty']))));

        $this->assertEquals(4, $totalQty(static::products()));
        $this->assertEquals(4, $pipedTotalQty(static::products()));
        $this->assertEquals($imperativeTotalQty, $totalQty(static::products()));
        $this->assertEquals(110, $amount(static::products()));
        $this->assertEquals($imperativeAmount, $amount(static::products()));
    }

    public function test_filter()
    {
        $valueGreaterThen35 = f\compose(f\gt(35), f\prop('value'));

        $this->assertEquals([
            2 => [
                'description' => 'boots',
                'qty' => 1,
                'value' => 40
            ]
        ], array_filter(static::products(), $valueGreaterThen35));
    }

    public function test_get_query_param()
    {
        $getParams = f\if_else('is_string', f\identity, 'http_build_query');
        $this->assertEquals('a=1&b=2', $getParams(f\prop('params', [
            'params' => [
                'a' => 1,
                'b' => 2,
            ]
        ])));
        $this->assertEquals('a=1&b=2', $getParams(f\prop('params', [
            'params' => 'a=1&b=2'
        ])));
    }

    public function test_json_encode_if_not_string()
    {
        // $response = !is_string($data['response']) ? json_encode($data['response']) : $data['response'];
        $prepareResponseToSave = f\if_else(f\not('is_string'), 'json_encode', f\identity);
        $this->assertEquals('OK', $prepareResponseToSave(f\prop('response', [
            'response' => 'OK'
        ])));
        $this->assertEquals('{"a":1,"b":2}', $prepareResponseToSave(f\prop('response', [
            'response' => [
                'a' => 1,
                'b' => 2,
            ]
        ])));
    }

    public function test_repeat_either()
    {
//        $shipper_country=strOr($obj['shipper_country'],$oldObj['shipper_country']);
//        $consignee_country=strOr($obj['consignee_country'],$oldObj['consignee_country']);
//        $pickup_hub_id = strOr($obj['pickup_hub_id'], $oldObj['pickup_hub_id']);
        $obj = [
            'shipper_country' => 'NL',
            'consignee_country' => '',
            'pickup_hub_id' => 5,
        ];
        $oldObj = [
            'shipper_country' => 'NL',
            'consignee_country' => 'US',
            'pickup_hub_id' => 5,
        ];
        $getProp = f\either(f\partial_r(f\prop, $obj), f\partial_r(f\prop, $oldObj));

        $this->assertEquals('NL', $getProp('shipper_country'));
        $this->assertEquals('US', $getProp('consignee_country'));
    }

    public function test_upper_specific_fields()
    {
        $obj = [
            'shipper_country' => 'nl',
            'consignee_country' => 'ca',
            'name' => 'John',
        ];

        $m_obj = array_merge($obj, f\map(f\ary('strtoupper', 1), f\select_keys(['shipper_country', 'consignee_country'], $obj)));
        $this->assertEquals('NL', f\prop('shipper_country', $m_obj));
        $this->assertEquals('CA', f\prop('consignee_country', $m_obj));
        $this->assertEquals('John', f\prop('name', $m_obj));

        $toUpperSomeFields = f\converge(
            'array_merge',
            [
                f\always($obj),
                f\pipe(f\select_keys(['shipper_country', 'consignee_country']), f\map(f\ary('strtoupper', 1)))
            ]
        );
        $m2_obj = $toUpperSomeFields($obj);
        $this->assertEquals('NL', f\prop('shipper_country', $m2_obj));
        $this->assertEquals('CA', f\prop('consignee_country', $m2_obj));
        $this->assertEquals('John', f\prop('name', $m2_obj));

        $m3_obj = f\map_keys('strtoupper', ['shipper_country', 'consignee_country'], $obj);
        $this->assertEquals('NL', f\prop('shipper_country', $m3_obj));
        $this->assertEquals('CA', f\prop('consignee_country', $m3_obj));
        $this->assertEquals('John', f\prop('name', $m3_obj));
    }
}

<?php
// +----------------------------------------------------------------------
// | multi-process
// +----------------------------------------------------------------------
// | Copyright (c) 2022
// +----------------------------------------------------------------------
// | Licensed MIT
// +----------------------------------------------------------------------
// | Author: js
// +----------------------------------------------------------------------
// | Date: 2022-07-09
// +----------------------------------------------------------------------
// | Time: 下午 03:22
// +----------------------------------------------------------------------

namespace phpth\process\supply;

use phpth\process\exception\CallException;

class Call
{
    /**
     * 将会根据数组元素个数开启相同数量的子进程，并且数组的每一个元素都将被传递到子进程回调函数的第二个参数中，否则将整个数据传递到子进程回调函数的第二个参数， 子进程的第一个参数为子进程编号
     */
    public const PROCESS_DATA_DISPATCH = -1;

    /**
     * @var array[][]
     */
    protected array $calls = [];

    /**
     * @param callable $call
     * @param array|null $param
     *
     * 如果设置常量为 Call::PROCESS_DATA_DISPATCH
     * 将会根据数组元素个数开启相同数量的子进程，
     * 并且数组的每一个元素都将被传递到子进程回调函数的第二个参数中，
     * 否则将整个数据传递到子进程回调函数的第二个参数，
     * 子进程的第一个参数为子进程编号
     * @param int|null $num 0, null for one call process
     * @param int|null $priority
     * @param string|null $name
     * @param int|null $child_restart if child process exit, how to do. default restart all exit child process
     * @return $this
     * @throws CallException
     */
    public function add(callable $call, ?array $param = [], ?int $num = 1, ?int $priority = 0, ?string $name = '', ?int $child_restart = ChildRestart::EXIT_ANY): static
    {
        $processDataDispatch = false;
        if ($num === Call::PROCESS_DATA_DISPATCH) {
            if (!$param) {
                throw new CallException("in PROCESS_DATA_DISPATCH model, param must not a empty array");
            }
            $processDataDispatch = true;
            $num = count($param);
        } else {
            $num = $num === null ? 1 : max($num, 1);
        }
        for ($i = 0; $i < $num; $i++) {
            $this->calls[] = [
                'name' => $name,
                'call' => $call,
                'param' => $processDataDispatch ? array_pop($param) : $param,
                'priority' => $priority ?: 0,
                'child_restart' => $child_restart === null ? ChildRestart::EXIT_ANY : $child_restart,
            ];
        }
        return $this;
    }

    /**
     * @param callable $call
     * @param array $param
     * @param string|null $name
     * @param int|null $processNo
     * @return string
     */
    public static function getCallName(callable $call, array $param, ?string $name = '', ?int $processNo = null): string
    {
        if ($name === null) {
            $name = '';
            goto end;
        }
        if (!$name) {
            is_callable($call, false, $call_str);
            $name = $call_str;
        }
        $p_str = '';
        foreach ($param as $v) {
            $type = gettype($v);
            $n_str = null;
            switch ($type) {
                case 'integer':
                case 'string':
                case 'double':
                case 'NULL':
                    $n_str = $v;
                    break;
                case 'array':
                    $n_str = '...';
                    break;
                case 'object':
                    $n_str = get_class($v);
                    break;
                case 'resource':
                    $n_str = get_resource_type($v);
                    break;
                case 'boolean':
                    $n_str = $v ? 'true' : 'false';
                    break;
            }
            $n_str === null ? $p_str .= ",$type" : $p_str .= ",$type:$n_str";
        }
        $p_str = '[' . trim($p_str, ',') . ']';
        $name = sprintf("$name%s: $p_str", $processNo);
        end:
        return $name;
    }

    /**
     * @param array $calls [0=> ['call'=>null, 'num'=> 1, 'param'=>[], 'child_restart'=> true, 'name'=> 'title']]
     *
     * @return $this
     * @throws CallException
     */
    public function multiAdd(array $calls = [0 => ['call' => null, 'num' => 1, 'param' => [], 'child_restart' => 0, 'priority' => 0, 'name' => 'title']]): static
    {
        foreach ($calls as $k => $call) {
            if (empty($call['call']) || !is_callable($call['call'])) {
                throw new CallException("not callable for index $k");
            }
            $this->add($call['call'], $call['param'] ?? null, $call['num'] ?? null, $call['priority'] ?? null, $call['name'] ?? null, $call['child_restart'] ?? null);
        }
        return $this;
    }

    /**
     * @return static
     */
    public function clear(): static
    {
        $this->calls = [];
        return $this;
    }

    /**
     * @return array[][]
     */
    public function getCalls(): array
    {
        return $this->calls;
    }
}

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
    public const EXIT_NO_START = 0;

    public const EXIT_NORMAL = 1;

    public const EXIT_SELF = 2;

    public const EXIT_SIGNAL = 4;

    public const EXIT_ALL = 6;

    /**
     * @var array[][]
     */
    protected array $calls = [];

    /**
     * @param callable $call
     * @param array|null $param
     * @param int|null $num 0, null for one call process
     * @param int|null $priority
     * @param string|null $name
     * @param int|null $restart_by if child process exit, how to do. default restart all exit child process
     *
     * @return $this
     */
    public function add(callable $call, ?array $param = [], ?int $num = 1, ?int $priority = 0, ?string $name = '', ?int $restart_by = Call::EXIT_ALL): self
    {
        $name = Call::getCallName($call, $param ?: [], $name);
        $num = $num === null ? 1 : max($num, 1);
        for ($i = 0; $i < $num; $i++) {
            $this->calls[] = [
                'name' => $name,
                'call' => $call,
                'param' => $param ?: [],
                'priority' => $priority ?: 0,
                'restart_by' => $restart_by === null ? Call::EXIT_ALL : $restart_by,
            ];
        }
        return $this;
    }

    /**
     * @param callable $call
     * @param array $param
     * @param string|null $name
     *
     * @return string
     */
    public static function getCallName(callable $call, array $param, ?string $name = ''): string
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
                case 'float':
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
        $name = "$name: $p_str";
        end:
        return $name;
    }

    /**
     * @param array $calls [0=> ['call'=>null, 'num'=> 1, 'param'=>[], 'restart_by'=> true, 'name'=> 'title']]
     *
     * @return $this
     * @throws CallException
     */
    public function multiAdd(array $calls = [0 => ['call' => null, 'num' => 1, 'param' => [], 'restart_by' => 0, 'priority' => 0, 'name' => 'title']]): self
    {
        foreach ($calls as $k => $call) {
            if (empty($call['call']) || !is_callable($call['call'])) {
                throw new CallException("not callable for index $k");
            }
            $this->add($call['call'], $call['param'] ?? null, $call['num'] ?? null, $call['priority'] ?? null, $call['name'] ?? null, $call['run_times'] ?? null);
        }
        return $this;
    }

    /**
     * @return void
     */
    public function clear()
    {
        $this->calls = [];
    }

    /**
     * @return array[][]
     */
    public function getCalls(): array
    {
        return $this->calls;
    }
}

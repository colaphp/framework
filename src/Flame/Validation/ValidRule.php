<?php

declare(strict_types=1);

namespace Flame\Validation;

/**
 * @method ValidRule confirm(mixed $field, string $msg = '') static 验证是否和某个字段的值一致
 * @method ValidRule different(mixed $field, string $msg = '') static 验证是否和某个字段的值是否不同
 * @method ValidRule egt(mixed $value, string $msg = '') static 验证是否大于等于某个值
 * @method ValidRule gt(mixed $value, string $msg = '') static 验证是否大于某个值
 * @method ValidRule elt(mixed $value, string $msg = '') static 验证是否小于等于某个值
 * @method ValidRule lt(mixed $value, string $msg = '') static 验证是否小于某个值
 * @method ValidRule eg(mixed $value, string $msg = '') static 验证是否等于某个值
 * @method ValidRule in(mixed $values, string $msg = '') static 验证是否在范围内
 * @method ValidRule notIn(mixed $values, string $msg = '') static 验证是否不在某个范围
 * @method ValidRule between(mixed $values, string $msg = '') static 验证是否在某个区间
 * @method ValidRule notBetween(mixed $values, string $msg = '') static 验证是否不在某个区间
 * @method ValidRule length(mixed $length, string $msg = '') static 验证数据长度
 * @method ValidRule max(mixed $max, string $msg = '') static 验证数据最大长度
 * @method ValidRule min(mixed $min, string $msg = '') static 验证数据最小长度
 * @method ValidRule after(mixed $date, string $msg = '') static 验证日期
 * @method ValidRule before(mixed $date, string $msg = '') static 验证日期
 * @method ValidRule expire(mixed $dates, string $msg = '') static 验证有效期
 * @method ValidRule allowIp(mixed $ip, string $msg = '') static 验证IP许可
 * @method ValidRule denyIp(mixed $ip, string $msg = '') static 验证IP禁用
 * @method ValidRule regex(mixed $rule, string $msg = '') static 使用正则验证数据
 * @method ValidRule token(mixed $token, string $msg = '') static 验证表单令牌
 * @method ValidRule is(mixed $rule = null, string $msg = '') static 验证字段值是否为有效格式
 * @method ValidRule isRequire(mixed $rule = null, string $msg = '') static 验证字段必须
 * @method ValidRule isNumber(mixed $rule = null, string $msg = '') static 验证字段值是否为数字
 * @method ValidRule isArray(mixed $rule = null, string $msg = '') static 验证字段值是否为数组
 * @method ValidRule isInteger(mixed $rule = null, string $msg = '') static 验证字段值是否为整形
 * @method ValidRule isFloat(mixed $rule = null, string $msg = '') static 验证字段值是否为浮点数
 * @method ValidRule isMobile(mixed $rule = null, string $msg = '') static 验证字段值是否为手机
 * @method ValidRule isIdCard(mixed $rule = null, string $msg = '') static 验证字段值是否为身份证号码
 * @method ValidRule isChs(mixed $rule = null, string $msg = '') static 验证字段值是否为中文
 * @method ValidRule isChsDash(mixed $rule = null, string $msg = '') static 验证字段值是否为中文字母及下划线
 * @method ValidRule isChsAlpha(mixed $rule = null, string $msg = '') static 验证字段值是否为中文和字母
 * @method ValidRule isChsAlphaNum(mixed $rule = null, string $msg = '') static 验证字段值是否为中文字母和数字
 * @method ValidRule isDate(mixed $rule = null, string $msg = '') static 验证字段值是否为有效格式
 * @method ValidRule isBool(mixed $rule = null, string $msg = '') static 验证字段值是否为布尔值
 * @method ValidRule isAlpha(mixed $rule = null, string $msg = '') static 验证字段值是否为字母
 * @method ValidRule isAlphaDash(mixed $rule = null, string $msg = '') static 验证字段值是否为字母和下划线
 * @method ValidRule isAlphaNum(mixed $rule = null, string $msg = '') static 验证字段值是否为字母和数字
 * @method ValidRule isAccepted(mixed $rule = null, string $msg = '') static 验证字段值是否为yes, on, 或是 1
 * @method ValidRule isEmail(mixed $rule = null, string $msg = '') static 验证字段值是否为有效邮箱格式
 * @method ValidRule isUrl(mixed $rule = null, string $msg = '') static 验证字段值是否为有效URL地址
 * @method ValidRule activeUrl(mixed $rule = null, string $msg = '') static 验证是否为合格的域名或者IP
 * @method ValidRule ip(mixed $rule = null, string $msg = '') static 验证是否有效IP
 * @method ValidRule fileExt(mixed $ext, string $msg = '') static 验证文件后缀
 * @method ValidRule fileMime(mixed $mime, string $msg = '') static 验证文件类型
 * @method ValidRule fileSize(mixed $size, string $msg = '') static 验证文件大小
 * @method ValidRule image(mixed $rule, string $msg = '') static 验证图像文件
 * @method ValidRule method(mixed $method, string $msg = '') static 验证请求类型
 * @method ValidRule dateFormat(mixed $format, string $msg = '') static 验证时间和日期是否符合指定格式
 * @method ValidRule unique(mixed $rule, string $msg = '') static 验证是否唯一
 * @method ValidRule behavior(mixed $rule, string $msg = '') static 使用行为类验证
 * @method ValidRule filter(mixed $rule, string $msg = '') static 使用filter_var方式验证
 * @method ValidRule requireIf(mixed $rule, string $msg = '') static 验证某个字段等于某个值的时候必须
 * @method ValidRule requireCallback(mixed $rule, string $msg = '') static 通过回调方法验证某个字段是否必须
 * @method ValidRule requireWith(mixed $rule, string $msg = '') static 验证某个字段有值的情况下必须
 * @method ValidRule must(mixed $rule = null, string $msg = '') static 必须验证
 */
class ValidRule
{
    // 验证字段的名称
    protected $title;

    // 当前验证规则
    protected $rule = [];

    // 验证提示信息
    protected $message = [];

    /**
     * 添加验证因子
     *
     * @param  string  $name  验证名称
     * @param  mixed  $rule  验证规则
     * @param  string  $msg  提示信息
     * @return $this
     */
    protected function addItem($name, $rule = null, $msg = '')
    {
        if ($rule || $rule === 0) {
            $this->rule[$name] = $rule;
        } else {
            $this->rule[] = $name;
        }

        $this->message[] = $msg;

        return $this;
    }

    /**
     * 获取验证规则
     *
     * @return array
     */
    public function getRule()
    {
        return $this->rule;
    }

    /**
     * 获取验证字段名称
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * 获取验证提示
     *
     * @return array
     */
    public function getMsg()
    {
        return $this->message;
    }

    /**
     * 设置验证字段名称
     *
     * @return $this
     */
    public function title($title)
    {
        $this->title = $title;

        return $this;
    }

    public function __call($method, $args)
    {
        if (strtolower(substr($method, 0, 2)) == 'is') {
            $method = substr($method, 2);
        }

        array_unshift($args, lcfirst($method));

        return call_user_func_array([$this, 'addItem'], $args);
    }

    public static function __callStatic($method, $args)
    {
        $rule = new static();

        if (strtolower(substr($method, 0, 2)) == 'is') {
            $method = substr($method, 2);
        }

        array_unshift($args, lcfirst($method));

        return call_user_func_array([$rule, 'addItem'], $args);
    }
}

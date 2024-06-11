<?php

declare(strict_types=1);

namespace Flame\Response;

use Exception;
use Flame\Http\Response;
use Flame\Support\Facade\Cookie;
use Flame\View\ViewManager as BaseView;

class View extends Response
{
    /**
     * 输出参数
     */
    protected array $options = [];

    /**
     * 输出变量
     */
    protected array $vars = [];

    /**
     * 输出type
     */
    protected string $contentType = 'text/html';

    /**
     * View对象
     */
    protected BaseView $view;

    /**
     * 是否内容渲染
     */
    protected bool $isContent = false;

    public function __construct($data = '', int $code = 200)
    {
        $this->init($data, $code);

        $this->cookie = Cookie::getInstance();
        $this->view = new BaseView();
    }

    /**
     * 设置是否为内容渲染
     */
    public function isContent(bool $content = true): static
    {
        $this->isContent = $content;

        return $this;
    }

    /**
     * 处理数据
     *
     * @throws Exception
     */
    protected function output($data): string
    {
        return $this->isContent ?
            $this->view->contentRender($data, $this->vars) :
            $this->view->render($data, $this->vars);
    }

    /**
     * 获取视图变量
     */
    public function getVars(?string $name = null)
    {
        if (is_null($name)) {
            return $this->vars;
        } else {
            return $this->vars[$name] ?? null;
        }
    }

    /**
     * 模板变量赋值
     */
    public function assign(string|array $name, $value = null): static
    {
        if (is_array($name)) {
            $this->vars = array_merge($this->vars, $name);
        } else {
            $this->vars[$name] = $value;
        }

        return $this;
    }

    /**
     * 检查模板是否存在
     */
    public function exists(string $name): bool
    {
        return $this->view->exists($name);
    }
}

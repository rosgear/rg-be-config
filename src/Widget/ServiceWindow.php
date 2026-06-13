<?php
/**
 * Этот файл является частью расширения модуля веб-приложения RosGear.
 * 
 * @link https://rosgear.ru/
 * @copyright Copyright (c) 2015 RosGear
 * @license https://rosgear.ru/license/
 */

namespace Rg\Backend\Config\Widget;

use Ge\Panel\Widget\Form;

/**
 * Виджет для формирования интерфейса окна редактирования настроек служб.
 * 
 * @author Anton Tivonenko <anton.tivonenko@gmail.com>
 * @package Rg\Backend\Config\Widget
 * @since 1.0
 */
class ServiceWindow extends \Ge\Panel\Widget\EditWindow
{
    /**
     * Параметры службы из Унифицированного конфигуратора.
     * 
     * @var array|null
     */
    public ?array $unified = [];

    /**
     * Служба.
     * 
     * @var \Ge\Stdlib\Service
     */
    public \Ge\Stdlib\Service $service;

    /**
     * {@inheritdoc}
     */
    public array $passParams = ['service', 'unified'];

    /**
     * {@inheritdoc}
     */
    protected function init(): void
    {
        parent::init();

        // окно компонента (Ext.window.Window Sencha ExtJS)
        $this->xtype = 'g-window';
        $this->id = $this->creator->viewId('window');
        $this->cls = 'g-window_profile';
        $this->title = sprintf(
            '%s <span>%s</span>', $this->creator->t('{name}'), $this->creator->t('{description}')
        );
        $this->titleTpl = $this->title;
        $this->icon = $this->creator->getIconUrl();
        $this->layout = 'fit';
        $this->ui = 'install';
        $this->resizable = false;

        // панель формы (Ge.view.form.Panel GeJS)
        $this->form->bodyPadding = 5;
        $this->form->router->setAll([
            'route' => $this->creator->route('/form'),
            'state' => Form::STATE_UPDATE,
            'rules' => [
                'submit' => '{route}/data/{id}',
                'update' => '{route}/update/{id}'
            ]
        ]);
        $this->form->setStateButtons(Form::STATE_UPDATE, ['help', 'save', 'cancel']);
        $this->form->items = $this->formItems();
    }

    /**
     * Возвращает поля формы.
     * 
     * @return array<int, mixed>
     */
    protected function formItems(): array
    {
        return [];
    }
}

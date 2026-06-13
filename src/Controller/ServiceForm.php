<?php
/**
 * Этот файл является частью модуля веб-приложения RosGear.
 * 
 * @link https://rosgear.ru/
 * @copyright Copyright (c) 2015 RosGear
 * @license https://rosgear.ru/license/
 */

namespace Rg\Backend\Config\Controller;

use Ge;
use Ge\Panel\Widget\Form;
use Ge\Mvc\Module\BaseModule;
use Ge\Panel\Http\Response;
use Ge\Panel\Widget\EditWindow;
use Ge\Panel\Controller\FormController;

/**
 * Контроллер формы конфигурации службы.
 * 
 * @author Anton Tivonenko <anton.tivonenko@gmail.com>
 * @package Rg\Backend\Config\Controller
 * @since 1.0
 */
class ServiceForm extends FormController
{
    /**
     * {@inheritdoc}
     * 
     * @var BaseModule|\Rg\Backend\Config\Module
     */
    public BaseModule $module;

    /**
     * {@inheritdoc}
     */
    public function createWidget(): EditWindow
    {
        /** @var EditWindow $window */
        $window = parent::createWidget();

        // окно компонента (Ext.window.Window Sencha ExtJS)
        $window->xtype = 'g-window';
        $window->id = $this->module->viewId('window');
        $window->cls = 'g-window_profile';
        $window->title = sprintf(
            '%s <span>%s</span>', $this->module->t('{name}'), $this->module->t('{description}')
        );
        $window->titleTpl = $window->title;
        $window->icon = $this->module->getIconUrl();
        $window->layout = 'fit';
        $window->ui = 'install';
        $window->resizable = false;

        // панель формы (Ge.view.form.Panel GeJS)
        $window->form->bodyPadding = 5;
        $window->form->router->setAll([
            'route' => $this->module->route('/form'),
            'state' => Form::STATE_UPDATE,
            'rules' => [
                'submit' => '{route}/data/{id}',
                'update' => '{route}/update/{id}'
            ]
        ]);
        $window->form->setStateButtons(Form::STATE_UPDATE, ['help', 'save', 'cancel']);
        return $window;
    }

    /**
     * Действие "update" изменяет конфигурацию службы.
     * 
     * @return Response
     */
    public function updateAction(): Response
    {
        /** @var Response $response */
        $response = $this->getResponse();
        /** @var \Ge\Http\Request $request */
        $request  = Ge::$app->request;
        /** @var \Ge\Panel\Data\Model\FormModel $model модель данных */
        $model = $this->getModel($this->defaultModel);
        if ($model === false) {
            $response
                ->meta->error(Ge::t('app', 'Could not defined data model "{0}"', [$this->defaultModel]));
            return $response;
        }

        // получение записи по идентификатору в запросе
        $form = $model->get();
        if ($form === null) {
            $form = $model;
        }

        // загрузка атрибутов в модель из запроса
        if (!$form->load($request->getPost())) {
            $response
                ->meta->error(Ge::t(BACKEND, 'No data to perform action'));
            return $response;
        }

        // валидация атрибутов модели
        if (!$form->validate()) {
            $response
                ->meta->error(Ge::t(BACKEND, 'Error filling out form fields: {0}', [$form->getError()]));
            return $response;
        }

        // сохранение атрибутов модели
        if (!$form->save()) {
            $response
                ->meta->error(
                    $form->hasErrors() ? $form->getError() : Ge::t(BACKEND, 'Could not save data')
                );
            return $response;
        }
        return $response;
    }
}

<?php
/**
 * Этот файл является частью модуля веб-приложения RosGear.
 * 
 * @link https://rosgear.ru/
 * @copyright Copyright (c) 2015 RosGear
 * @license https://rosgear.ru/license/
 */

namespace Rg\Backend\Config\Model;

use Ge;
use Ge\Mvc\Module\BaseModule;
use Ge\Panel\Data\Model\FormModel;

/**
 * Модель данных конфигурации службы.
 * 
 * Добавляет настройки в унифицированный конфигуратор.
 * 
 * @author Anton Tivonenko <anton.tivonenko@gmail.com>
 * @package Rg\Backend\Config\Model
 * @since 1.0
 */
class ServiceForm extends FormModel
{
    /**
     * {@inheritdoc}
     * 
     * @var BaseModule|\Rg\Backend\Config\Module
     */
    public BaseModule $module;

    /**
     * Имя раздела настроек в унифицированном конфигураторе.
     * 
     * @var string
     */
    protected string $unifiedName = '';

    /**
     * {@inheritdoc}
     */
    public function init(): void
    {
        parent::init();

        $this
            ->on(self::EVENT_AFTER_SAVE, function ($isInsert, $columns, $result, $message) {
                if ($this->useResetConfig()) {
                    $message['message'] = $this->module->t('settings reseted {0} successfully', [$this->module->t('{name}')]);
                    $message['title']   = $this->t('Reset settings');
                } else {
                    $message['message'] = $this->module->t('settings saved {0} successfully', [$this->module->t('{name}')]);
                    $message['title']   = $this->t('Save settings');
                }
                $message['type'] = 'accept';
        
                // всплывающие сообщение
                $this->response()
                    ->meta
                        ->cmdPopupMsg($message['message'], $message['title'], $message['type']);
            });
    }

    /**
     * {@inheritdoc}
     */
    public function isNewRecord(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getDirtyAttributes(?array $names = null): array
    {
        if ($names === null)
            $names = array_keys($this->attributes);
        $attributes = [];
        if ($this->oldAttributes === null) {
            foreach ($names as $name) {
                if (isset($this->attributes[$name])) {
                     $attributes[$name] = $this->attributes[$name];
                }
            }
        } else {
            foreach ($names as $name) {
                if (isset($this->attributes[$name]) && isset($this->oldAttributes[$name])) {
                $attributes[$name] = $this->attributes[$name];
                }
            }
        }
        return $attributes;
    }

    /**
     * {@inheritdoc}
     */
    public function update(bool $useValidation = false, ?array $attributes = null): false|int
    {
        // если сброс настроеек, то нет смысла делать валидацию
        if ($this->useResetConfig()) {
            $this->resetConfig();
            $this->afterSave(false);
            return 1;
        }
        return parent::update($useValidation, $attributes);
    }

    /**
     * {@inheritdoc}
     */
    protected function updateProcess(?array $attributes = null): false|int
    {
        if (!$this->beforeSave(false)) {
            return false;
        }
        // т.к. необходимо сохранить все атрибуты без проверки на изменение,
        // то: attributes = getDirtyAttributes
        $dirtyAttributes = $this->attributes;
        if (empty($dirtyAttributes)) {
            $this->afterSave(false);
            return 0;
        }
        // возвращает атрибуты без псевдонимов (если они были указаны)
        $columns = $this->unmaskedAttributes($dirtyAttributes);
        $this->beforeUpdate($columns);
        // сохранение настроек модуля
        $this->saveConfig($columns);
        $this->setOldAttributes($this->attributes);
        $this->afterSave(false, $columns);
        return 1;
    }

    /**
     * Проверяет флаг сброса настроеек расширения.
     * 
     * @return bool
     */
    public function useResetConfig(): bool
    {
        return $this->getUnsafeAttribute('reset') !== null;
    }

    /**
     * Сохраняет настройки расширения.
     * 
     * @param array $parameters Опции (параметры) настройки расширения.
     * 
     * @return $this
     */
    public function saveConfig(?array $parameters): static
    {
        Ge::$app->unifiedConfig->{$this->unifiedName} = $parameters;
        Ge::$app->unifiedConfig->save();
        return $this;
    }

    /**
     * Удаляет (сбрасывает) раздел настроек расширения.
     * 
     * @return void
     */
    public function resetConfig(): void
    {
        $names = $this->excludedAttributes();
        // если есть опции принадлежащие другой службе, чтобы 
        // их сохранить при сбросе
        if ($names) {
            $options = Ge::$app->unifiedConfig->{$this->unifiedName};
            if ($options === null) {
                $options = $this->selectFromService();
            }
            // если есть опции конфигурации
            if ($options) {
                $result = [];
                foreach($names as $name) {
                    if (isset($options[$name]))
                        $result[$name] = $options[$name];
                }
            }
            if ($result) {
                $this->saveConfig($result);
                return;
            }
        }
        if (Ge::$app->unifiedConfig->remove($this->unifiedName))
            Ge::$app->unifiedConfig->save();
    }

    /**
     * Загрузка настроек расширения в атрибуты модели.
     * 
     * @return array|string|null
     */
    public function selectFromService(): string|array|null
    {
        return Ge::$app->services->config->{$this->unifiedName};
    }

    /**
     * Загрузка настроек расширения из унифицированного конфигуратора 
     * в атрибуты модели.
     * 
     * @return ServiceForm|null
     */
    public function selectFromConfig(): ?static
    {
        $row = Ge::$app->unifiedConfig->{$this->unifiedName};
        if ($row === null) {
            $row = $this->selectFromService();
            // если $row было представлено как: `'serviceName' => 'serviceClass'`
            // и не имеет параметров по умолчанию
            if (is_string($row)) {
                $this->reset();
                return $this;
            }
        }
        if ($row) {
            $this->reset();
            $this->afterSelect();
            $this->populate($this, $row);
            $this->afterPopulate();
            return $this;
        } else
            return null;
    }

    /**
     * {@inheritdoc}
     */
    public function get(mixed $identifier = null): ?static
    {
        return $this->selectFromConfig();
    }

    /**
     * Возвращает имена опций текущей конфигурации, которые принадлежат другой 
     * службе или расширению.
     * 
     * Такие опции необходимо учитывать при сбросе конфигурации, т.к. они могут 
     * повлиять на работу других расширений.
     * 
     * @return array
     */
    public function excludedAttributes(): array
    {
        return [];
    }
}

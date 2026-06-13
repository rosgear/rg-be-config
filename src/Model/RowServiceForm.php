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
 * Модель данных набора параметров (строка) конфигурации службы.
 * 
 * Добавляет настройки в унифицированный конфигуратор.
 * 
 * @author Anton Tivonenko <anton.tivonenko@gmail.com>
 * @package Rg\Backend\Config\Model
 * @since 1.0
 */
class RowServiceForm extends FormModel
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
     * Имя параметра из GET запроса для получения набора параметров (строки) из 
     * конфигурации службы.
     *
     * @var string
     */
    protected string $rowIdParam = 'id';

    /**
     * {@inheritdoc}
     */
    public function getIdentifier(): mixed
    {
        return Ge::$app->request->getQuery($this->rowIdParam, '', 'string');
    }

    /**
     * {@inheritdoc}
     */
    public function isNewRecord(): bool
    {
        return $this->getIdentifier() === '';
    }

    /**
     * {@inheritdoc}
     */
    public function getDirtyAttributes(?array $names = null): array
    {
        if ($names === null)
            $names = array_keys($this->attributes);
        $attributes = array();
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
    protected function deleteProcess(): false|int
    {
        $this->result = false;
        if ($this->beforeDelete()) {
            $this->saveConfig(null, $this->getIdentifier());
            $this->result = 1;
            // сброс атрибутов записи
            $this->attributes = [];
            $this->oldAttributes = [];
            $this->afterDelete($this->result);
        }
        return $this->result;
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

        /** @var array $parameters Параметры (атрибуты) без псевдонимов (если они были указаны) */
        $parameters = $this->unmaskedAttributes($dirtyAttributes);
        $this->beforeUpdate($parameters);
        /** @var mixed $id Идентификатор параметров службы */
        $id = $this->valuePrimaryKey();
        // если невозможно получить идентификатор
        if (empty($id)) {
            return false;
        }

        // сохранение настроек модуля
        $this->result = 1;
        $this->saveConfig($parameters, $id);
        $this->setOldAttributes($this->attributes);
        $this->afterSave(false, $parameters, $this->result );
        return $this->result;
    }

    /**
     * {@inheritdoc}
     */
    protected function insertProcess(?array $attributes = null): false|int|string
    {
        if (!$this->beforeSave(true)) {
            return false;
        }

        // возвращает атрибуты без псевдонимов (если они были указаны)
        $parameters = $this->unmaskedAttributes($this->attributes);

        // дополнительные атрибуты
        $append = $this->appendAttributes(true);
        if ($append) {
            $parameters = array_merge($parameters, $append);
        }

        $this->beforeInsert($parameters);
        /** @var mixed $id Идентификатор параметров службы */
        $id = $parameters[$this->primaryKey()] ?? null;
        // если невозможно получить идентификатор
        if (empty($id)) {
            return false;
        }

        // добавление записи
        $this->saveConfig($parameters, $id);
        $this->result = 1;
        $this->afterSave(true, $parameters, $this->result);
        return $this->result;
    }

    /**
     * Сохраняет набора параметров (строку) конфигурации службы.
     * 
     * @param array $parameters Набор параметров (строка).
     * @param string|int $identifier Идентификатор набора параметров.
     * 
     * @return $this
     */
    public function saveConfig(?array $parameters, string|int $identifier): static
    {
        /** @var \Ge\Config\Config $config */
        $config = Ge::$app->unifiedConfig;
        /** @var mixed $unifiedParameters Все параметры службы */
        $unifiedParameters = $config->{$this->unifiedName};
        if ($unifiedParameters) {
            if ($parameters === null)
                unset($unifiedParameters[$identifier]);
            else
                $unifiedParameters[$identifier] = $parameters;
            $config->{$this->unifiedName} = $unifiedParameters;
            $config->save();
        }
        return $this;
    }

    /**
     * Выполняет загрузку набора параметров (строку) из конфигурации службы.
     * 
     * @param mixed $identifier Идентификатор набора параметров (строки).
     * 
     * @return array|string|null Возвращает набора параметров (строку) конфигурации службы.
     */
    public function selectFromService(mixed $identifier = null): string|array|null
    {
        /** @var mixed $params */
        $params = Ge::$app->services->config->{$this->unifiedName};
        if ($params)
            return $identifier ? $params[$identifier] ?? null : $params;
        else
            return null;
    }

    /**
     * Выполняет загрузку набора параметров (строку) из унифицированного 
     * конфигуратора в атрибуты модели.
     * 
     * @param mixed $identifier Идентификатор набора параметров (строки).
     * 
     * @return ServiceForm|null
     */
    public function selectFromConfig(mixed $identifier = null): ?static
    {
        if ($identifier === null ) {
            /** @var mixed $identifier */
            $identifier = $this->getIdentifier();
        }

        /** @var mixed $params Параметры службы */
        $params = Ge::$app->unifiedConfig->{$this->unifiedName};
        if ($params)
            $row = $identifier ? $params[$identifier] ?? null : false;
        else
            $row = false;

        if ($row === false) {
            $row = $this->selectFromService($identifier);
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
        return $this->selectFromConfig($identifier);
    }

    /**
     * {@inheritdoc}
     */
    public function excludedAttributes(): array
    {
        return [];
    }
}

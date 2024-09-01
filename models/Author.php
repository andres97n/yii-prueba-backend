<?php

namespace app\models;

use app\constants\GlobalConstants;
use yii\mongodb\ActiveRecord;

/**
 * Este modelo hace refencia a la colección 'authors'
 * @property string fullname Nombre del Autor
 * @property string $birthdate Fecha de nacimiento
 * @property array $autores Autores
 * @property array $state Estado de registro
 *
 * @author Andrés Novillo <anovillo@libelulasoft.com>
 * @since 0.0.1
 */
class Author extends ActiveRecord
{
    /**
     * Nombres de base de datos y colección
     * @return array
     */
    public static function collectionName()
    {
        return ['library', 'authors'];
    }

    /**
     * Validaciones y tipos de datos de cada atributo de la colección
     * @return array
     */
    public function rules(): array
    {
        return [
            [['fullname'], 'required', 'message' => 'El nombre completo del autor es obligatorio'],
            [['birthdate'], 'required', 'message' => 'La fecha de nacimiento del autor es obligatorio'],
            [['birthdate'], 'date', 'format' => 'php:Y-m-d'],
            [['state'], 'required', 'message' => 'El estado es obligatorio.'],
            [['fullname', 'state'], 'string'],
            [['books'], 'validateBooksArray'],
        ];
    }

    /**
     * Lista de atributos
     * @return string[]
     */
    public function attributes(): array
    {
        return [
            '_id',
            'fullname',
            'birthdate',
            'books',
            'state'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels():array
    {
        return [
            'fullname' => 'Nombre completo',
            'birthdate' => 'Fecha de nacimiento',
            'books' => 'Libros',
            'state' => 'Estado',
        ];
    }

    /**
     * Valida si existe el Autor por su nombre
     * @param string $fullname
     * @return Author|null
     */
    public static function existsFullname(string $fullname)
    {
        return self::findOne(['fullname' => $fullname, 'state' => GlobalConstants::STATE_ACTIVO]);
    }

    /**
     * Valida si 'books' es un array
     * {@inheritdoc}
     */
    public function validateBooksArray($attribute, $params)
    {
        if (!is_array($this->$attribute)) {
            $this->addError($attribute, "'books' debe ser un array");
        }
    }

    /**
     * Retorna todos los autores activos
     * @return ActiveRecord[]
     */
    public static function getAuthors()
    {
        return self::find()->where(['state' => GlobalConstants::STATE_ACTIVO])->all();
    }

    /**
     * Retorna un autor por su _id
     * @return Author|null
     */
    public static function getAuthorById(string $id)
    {
        return self::findOne(['_id' => $id, 'state' => GlobalConstants::STATE_ACTIVO]);
    }
}
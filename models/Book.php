<?php

namespace app\models;

use app\constants\GlobalConstants;
use yii\mongodb\ActiveRecord;

/**
 * Este modelo hace refencia a la colección 'books'
 * @property string $title Título
 * @property string $publicationYear Año de publicación
 * @property string|null $description Descripción
 * @property array $authors Autores
 * @property array $state Estado de registro
 *
 * @author Andrés Novillo <anovillo@libelulasoft.com>
 * @since 0.0.1
 */
class Book extends ActiveRecord
{
    /**
     * Nombres de base de datos y colección
     * @return array
     */
    public static function collectionName(): array
    {
        return ['library', 'books'];
    }

    /**
     * Validaciones y tipos de datos de cada atributo de la colección
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            [['title',], 'required', 'message' => 'El título es obligatorio.'],
            [['publicationYear',], 'required', 'message' => 'El año de publicación es obligatorio.'],
            [['description',], 'required', 'message' => 'La descripción es obligatoria.'],
            [['state'], 'required', 'message' => 'El estado es obligatorio.'],
            [['title', 'description', 'state'], 'string'],
            [['publicationYear'], 'date', 'format' => 'php:Y'],
            [['authors'], 'validateAuthorsArray'],
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
            'title',
            'publicationYear',
            'description',
            'authors',
            'state',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels():array
    {
        return [
            'title' => 'Titulo',
            'publicationYear' => 'Año de publicación',
            'description' => 'Descripción',
            'authors' => 'Autores',
            'state' => 'Estado',
        ];
    }

    /**
     * Valida si 'authors' es un array
     * {@inheritdoc}
     */
    public function validateAuthorsArray($attribute, $params)
    {
        if (!is_array($this->$attribute)) {
            $this->addError($attribute, "'authors' debe ser un array");
        }
    }

    /**
     * Valida si existe un libro por su título y año de publicación
     * @return Book|null
     */
    public static function existsBook($title, $publicationYear)
    {
        return self::findOne(['title'=>$title, 'publicationYear'=>$publicationYear, 'state'=>GlobalConstants::STATE_ACTIVO ]);
    }

    /**
     * Retorna todos los libros activos
     * @return ActiveRecord[]
     */
    public static function getBooks(): array
    {
        return self::find()->where(['state' => GlobalConstants::STATE_ACTIVO])->all();
    }

    /**
     * Retorna un libro por su _id
     * @return Book|null
     */
    public static function getBookById(string $id)
    {
        return self::findOne(['_id' => $id, 'state' => GlobalConstants::STATE_ACTIVO]);
    }
}
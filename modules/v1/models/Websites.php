<?php

namespace app\modules\v1\models;

use Yii;

/**
 * This is the model class for table "websites".
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $access_token
 * @property string|null $created_at
 * @property string|null $updated_at
 *
 * @property Bookings[] $bookings
 */
class Websites extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'websites';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'email', 'access_token'], 'required'],
            [['created_at', 'updated_at'], 'safe'],
            [['name', 'email', 'access_token'], 'string', 'max' => 255],
            [['email'], 'unique'],
            [['access_token'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'email' => 'Email',
            'access_token' => 'Access Token',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * Gets query for [[Bookings]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getBookings()
    {
        return $this->hasMany(Bookings::class, ['website_id' => 'id']);
    }



    /**
     * Generate access token
     */
    public function generateAccessToken()
    {
        $this->access_token = Yii::$app->security->generateRandomString(40);
    }

}

<?php

namespace app\modules\v1\models;

use Yii;

/**
 * This is the model class for table "bookings".
 *
 * @property int $id
 * @property int $website_id
 * @property string $service_name
 * @property string $customer_name
 * @property string|null $customer_contact
 * @property string $booking_date
 * @property string $start_time
 * @property string $end_time
 * @property int|null $duration_minutes
 * @property string|null $created_at
 *
 * @property Websites $website
 */
class Bookings extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'bookings';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['website_id', 'service_name', 'customer_name', 'booking_date', 'start_time', 'end_time'], 'required'],
            [['website_id', 'duration_minutes'], 'integer'],
            [['booking_date', 'start_time', 'end_time', 'created_at'], 'safe'],
            [['service_name', 'customer_name', 'customer_contact'], 'string', 'max' => 255],
            [['website_id', 'booking_date', 'start_time', 'end_time'], 'unique', 'targetAttribute' => ['website_id', 'booking_date', 'start_time', 'end_time']],
            [['website_id'], 'exist', 'skipOnError' => true, 'targetClass' => Websites::class, 'targetAttribute' => ['website_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'website_id' => 'Website ID',
            'service_name' => 'Service Name',
            'customer_name' => 'Customer Name',
            'customer_contact' => 'Customer Contact',
            'booking_date' => 'Booking Date',
            'start_time' => 'Start Time',
            'end_time' => 'End Time',
            'duration_minutes' => 'Duration Minutes',
            'created_at' => 'Created At',
        ];
    }

    /**
     * Gets query for [[Website]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getWebsite()
    {
        return $this->hasOne(Websites::class, ['id' => 'website_id']);
    }
}

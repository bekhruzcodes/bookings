<?php

namespace app\migrations\v1;

use yii\db\Migration;

/**
 * Handles the creation of tables `bookings` and `websites`.
 */
class m241226_123456_create_tables extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function up()
    {
        // Create `websites` table
        $this->createTable('{{%websites}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(255)->notNull(),
            'email' => $this->string(255)->notNull()->unique(),
            'access_token' => $this->string(255)->notNull()->unique(),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP')->append('ON UPDATE CURRENT_TIMESTAMP'),
        ]);

        // Create `bookings` table
        $this->createTable('{{%bookings}}', [
            'id' => $this->primaryKey(),
            'website_id' => $this->integer()->notNull(),
            'service_name' => $this->string(255)->notNull(),
            'customer_name' => $this->string(255)->notNull(),
            'customer_contact' => $this->string(255)->notNull(), // Contact must be present
            'booking_date' => $this->date()->notNull(),
            'start_time' => $this->time()->notNull(),
            'end_time' => $this->time()->notNull(),
            'duration_minutes' => $this->integer()->notNull()->check("duration_minutes IN (30, 60, 90, 120)"),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        // Create foreign key for `bookings` table
        $this->addForeignKey(
            'fk-bookings-website_id',
            '{{%bookings}}',
            'website_id',
            '{{%websites}}',
            'id',
            'CASCADE'
        );

        // Add composite unique index for preventing overlapping bookings
        $this->createIndex(
            'idx-bookings-unique',
            '{{%bookings}}',
            ['website_id', 'booking_date', 'start_time', 'end_time'],
            true
        );
    }

    /**
     * {@inheritdoc}
     */
    public function down()
    {
        // Drop composite unique index
        $this->dropIndex('idx-bookings-unique', '{{%bookings}}');

        // Drop foreign key for `bookings` table
        $this->dropForeignKey('fk-bookings-website_id', '{{%bookings}}');

        // Drop tables in reverse order
        $this->dropTable('{{%bookings}}');
        $this->dropTable('{{%websites}}');
    }
}

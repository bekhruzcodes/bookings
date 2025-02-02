<?php

use yii\db\Migration;

/**
 * Class m250117_175442_update_duration_minutes
 */
class m250117_175442_update_duration_minutes extends Migration
{

    public function safeUp()
    {



        // Modify the `duration_minutes` column to include 15 and 45 minutes
        $this->alterColumn('{{%bookings}}', 'duration_minutes', $this->integer()->notNull()->check("duration_minutes IN (15, 30, 45, 60, 90, 120)"));

        // Add a new `status` column
        $this->addColumn('{{%bookings}}', 'status', $this->string(50)->notNull()->defaultValue('pending'));
    }

    public function safeDown()
    {
        // Revert the `duration_minutes` column to the original constraint
        $this->alterColumn('{{%bookings}}', 'duration_minutes', $this->integer()->notNull()->check("duration_minutes IN (30, 60, 90, 120)"));

        // Drop the `status` column
        $this->dropColumn('{{%bookings}}', 'status');
    }
}
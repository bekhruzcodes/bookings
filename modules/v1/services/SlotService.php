<?php

namespace app\modules\v1\services;

use app\modules\v1\models\Bookings;
use DateTime;
use DateInterval;

class SlotService
{
    private const WORKING_HOURS_START = '09:00';
    private const WORKING_HOURS_END = '17:00';

    public function getAvailableSlots(int $websiteId, string $date, int $durationMinutes): array
    {
        $startOfDay = new DateTime("$date " . self::WORKING_HOURS_START);
        $endOfDay = new DateTime("$date " . self::WORKING_HOURS_END);

        // Get all bookings for the given date
        $bookings = $this->getBookingsForDate($websiteId, $date);

        // Sort bookings by start time to process them in order
        usort($bookings, function($a, $b) {
            return strcmp($a['start_time'], $b['start_time']);
        });

        // Generate available time slots considering bookings
        return $this->generateAvailableSlots($startOfDay, $endOfDay, $bookings, $durationMinutes, $date);
    }

    private function getBookingsForDate(int $websiteId, string $date): array
    {
        return Bookings::find()
            ->select(["TIME_FORMAT(start_time, '%H:%i') AS start_time", "TIME_FORMAT(end_time, '%H:%i') AS end_time"])
            ->where(['website_id' => $websiteId])
            ->andWhere(['DATE(booking_date)' => $date])
            ->andWhere("start_time < end_time") // Ensure valid time range
            ->orderBy(['start_time' => SORT_ASC])
            ->asArray()
            ->all();
    }

    private function generateAvailableSlots(
        DateTime $startOfDay,
        DateTime $endOfDay,
        array $bookings,
        int $durationMinutes,
        string $date
    ): array {
        $availableSlots = [];
        $currentTime = clone $startOfDay;

        // If no bookings, generate all possible slots
        if (empty($bookings)) {
            while ($currentTime < $endOfDay) {
                $nextSlot = clone $currentTime;
                $nextSlot->add(new DateInterval("PT{$durationMinutes}M"));

                if ($nextSlot <= $endOfDay) {
                    $availableSlots[] = $currentTime->format('H:i');
                }

                $currentTime->add(new DateInterval("PT{$durationMinutes}M"));
            }

            return $availableSlots;
        }

        // Handle case with bookings
        foreach ($bookings as $index => $booking) {
            $bookingStart = new DateTime("$date " . $booking['start_time']);
            $bookingEnd = new DateTime("$date " . $booking['end_time']);

            // Add slots before the current booking
            while ($currentTime < $bookingStart) {
                $slotEnd = clone $currentTime;
                $slotEnd->add(new DateInterval("PT{$durationMinutes}M"));

                if ($slotEnd <= $bookingStart) {
                    $availableSlots[] = $currentTime->format('H:i');
                }

                $currentTime->add(new DateInterval("PT{$durationMinutes}M"));
            }

            // Move current time to the end of the booking
            $currentTime = clone $bookingEnd;
        }

        // Add remaining slots after the last booking
        while ($currentTime < $endOfDay) {
            $slotEnd = clone $currentTime;
            $slotEnd->add(new DateInterval("PT{$durationMinutes}M"));

            if ($slotEnd <= $endOfDay) {
                $availableSlots[] = $currentTime->format('H:i');
            }

            $currentTime->add(new DateInterval("PT{$durationMinutes}M"));
        }

        return $availableSlots;
    }
}
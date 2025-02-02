<?php

namespace app\modules\v1\services;

use app\modules\v1\models\Bookings;
use DateTime;
use DateInterval;
use DateTimeZone;
use Exception;

class SlotService
{
    private const WORKING_HOURS_START = '09:00';
    private const WORKING_HOURS_END = '17:00';
    private const MIN_HOURS_IN_ADVANCE = 2;
    private const TIMEZONE = 'Asia/Tashkent'; // Adjust this to your timezone

    public function getAvailableSlots(int $websiteId, string $date, int $durationMinutes): array
    {
        $timezone = new DateTimeZone(self::TIMEZONE);

        // Create DateTime objects with explicit timezone
        $now = new DateTime('now', $timezone);
        $requestedDate = new DateTime($date, $timezone);
        $today = new DateTime('today', $timezone);

        // For debugging
        error_log("Current time: " . $now->format('Y-m-d H:i:s'));
        error_log("Minimum booking time: " . $now->add(new DateInterval('PT' . self::MIN_HOURS_IN_ADVANCE . 'H'))->format('Y-m-d H:i:s'));

        // Reset $now as it was modified in the debug statement
        $now = new DateTime('now', $timezone);

        // Check if the requested date is in the past
        if ($requestedDate < $today) {
            return []; // Return empty array for past dates
        }

        $startOfDay = new DateTime("$date " . self::WORKING_HOURS_START, $timezone);
        $endOfDay = new DateTime("$date " . self::WORKING_HOURS_END, $timezone);

        // If the requested date is today, adjust start time to be 2 hours from now
        if ($requestedDate->format('Y-m-d') === $today->format('Y-m-d')) {
            $minStartTime = (clone $now)->add(new DateInterval('PT' . self::MIN_HOURS_IN_ADVANCE . 'H'));

            // If minimum start time is after working hours, return empty array
            if ($minStartTime > $endOfDay) {
                return [];
            }

            // Adjust start of day if minimum start time is after regular start time
            if ($minStartTime > $startOfDay) {
                // Round up to the next slot
                $minutes = (int) $minStartTime->format('i');
                if ($minutes > 0) {
                    $roundUpTo = $durationMinutes - ($minutes % $durationMinutes);
                    if ($minutes % $durationMinutes > 0) {
                        $minStartTime->add(new DateInterval("PT{$roundUpTo}M"));
                    }
                }
                $startOfDay = $minStartTime;
            }
        }

        // Get all bookings for the given date
        $bookings = $this->getBookingsForDate($websiteId, $date);

        // Generate available time slots considering bookings
        $availableSlots = $this->generateAvailableSlots($startOfDay, $endOfDay, $bookings, $durationMinutes, $date);

        // For today, filter out any slots that are less than 2 hours from now
        if ($requestedDate->format('Y-m-d') === $today->format('Y-m-d')) {
            $minTime = (clone $now)->add(new DateInterval('PT' . self::MIN_HOURS_IN_ADVANCE . 'H'));
            $availableSlots = array_filter($availableSlots, function($slot) use ($date, $minTime, $timezone) {
                $slotTime = new DateTime("$date $slot", $timezone);
                return $slotTime >= $minTime;
            });

            // Reindex array after filtering
            $availableSlots = array_values($availableSlots);
        }

        return $availableSlots;
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
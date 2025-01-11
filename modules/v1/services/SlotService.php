<?php


namespace app\modules\v1\services;

use app\modules\v1\models\Bookings;
use DateTime;
use DateInterval;


class SlotService
{
    private const WORKING_HOURS_START = '09:00';
    private const WORKING_HOURS_END = '17:00';

    /**
     * Get available slots for a specific website, date, and duration.
     * @param int $websiteId
     * @param string $date Date in Y-m-d format
     * @param int $durationMinutes
     * @return array
     * @throws \DateMalformedStringException
     */
    public function getAvailableSlots(int $websiteId, string $date, int $durationMinutes): array
    {
        $startOfDay = new DateTime("$date " . self::WORKING_HOURS_START);
        $endOfDay = new DateTime("$date " . self::WORKING_HOURS_END);

        $bookings = $this->getBookingsForDate($websiteId, $date);

        return $this->calculateAvailableSlots($bookings, $startOfDay, $endOfDay, $durationMinutes);
    }

    /**
     * Get all bookings for a specific website and date.
     * @param int $websiteId
     * @param string $date
     * @return array
     */
    private function getBookingsForDate(int $websiteId, string $date): array
    {
        return Bookings::find()
            ->select(['start_time', 'end_time'])
            ->where(['website_id' => $websiteId])
            ->andWhere(['DATE(start_time)' => $date])
            ->orderBy(['start_time' => SORT_ASC])
            ->asArray()
            ->all();
    }

    /**
     * Calculate available slots between bookings.
     * @param array $bookings
     * @param DateTime $startOfDay
     * @param DateTime $endOfDay
     * @param int $durationMinutes
     * @return array
     * @throws \DateMalformedStringException
     */
    private function calculateAvailableSlots(array $bookings, DateTime $startOfDay, DateTime $endOfDay, int $durationMinutes): array
    {
        $availableSlots = [];
        $currentTime = clone $startOfDay;

        foreach ($bookings as $booking) {
            $bookingStart = new DateTime($booking['start_time']);
            $bookingEnd = new DateTime($booking['end_time']);

            // Fill slots before the booking
            while ($currentTime < $bookingStart) {
                $nextSlot = clone $currentTime;
                $nextSlot->add(new DateInterval("PT{$durationMinutes}M"));

                if ($nextSlot <= $bookingStart && $nextSlot <= $endOfDay) {
                    $availableSlots[] = $currentTime->format('H:i');
                }

                $currentTime = $nextSlot;
            }

            // Move the current time to the end of the booking
            $currentTime = max($bookingEnd, $currentTime);
        }

        // Fill remaining slots after the last booking
        while ($currentTime < $endOfDay) {
            $nextSlot = clone $currentTime;
            $nextSlot->add(new DateInterval("PT{$durationMinutes}M"));

            if ($nextSlot <= $endOfDay) {
                $availableSlots[] = $currentTime->format('H:i');
            }

            $currentTime = $nextSlot;
        }

        return $availableSlots;
    }
}

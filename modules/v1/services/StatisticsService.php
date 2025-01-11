<?php

namespace app\modules\v1\services;

use app\modules\v1\models\Bookings;
use Yii;

class StatisticsService
{
    private $websiteId;

    public function __construct($websiteId)
    {
        if (!$websiteId) {
            throw new \yii\web\UnauthorizedHttpException('Authentication failed.');
        }
        $this->websiteId = $websiteId;
    }

    /**
     * Get booking statistics for the last 30 days.
     */
    public function getStatistics(): array
    {
        $last30Days = date('Y-m-d', strtotime('-30 days'));
        $previous30Days = date('Y-m-d', strtotime('-60 days'));

        $totalBookingsLast30Days = $this->countBookings($last30Days);
        $totalBookingsPrevious30Days = $this->countBookings($previous30Days, $last30Days);

        $bookingChangePercentage = $this->calculatePercentageChangeForTotal(
            $totalBookingsLast30Days,
            $totalBookingsPrevious30Days
        );

        return [
            'totalBookings' => [
                'count' => $totalBookingsLast30Days,
                'percentage' => $bookingChangePercentage,
            ],
            'mostSellingTime' => $this->getMostSellingTime($last30Days, $totalBookingsLast30Days),
            'mostSellingDay' => $this->getMostSellingDay($last30Days, $totalBookingsLast30Days),
            'mostSellingDuration' => $this->getMostSellingDuration($last30Days, $totalBookingsLast30Days),
            'mostSellingService' => $this->getMostSellingService($last30Days, $totalBookingsLast30Days),
            'returnClients' => $this->getReturningClients($last30Days),
        ];
    }

    private function countBookings($startDate, $endDate = null): int
    {
        $query = Bookings::find()
            ->where(['website_id' => $this->websiteId])
            ->andWhere(['>=', 'booking_date', $startDate]);

        if ($endDate) {
            $query->andWhere(['<', 'booking_date', $endDate]);
        }

        return (int)$query->count();
    }

    private function calculatePercentageChangeForTotal($current, $previous): float
    {
        if ($previous > 0) {
            return round((($current - $previous) / $previous) * 100, 2);
        }
        return $current > 0 ? 100 : 0;
    }

    private function calculatePercentageForCategory($count, $total): float
    {
        if ($total > 0) {
            return round(($count / $total) * 100, 2);
        }
        return 0;
    }

    private function getMostSellingTime($startDate, $totalBookings): array
    {
        $mostSellingTime = Bookings::find()
            ->select(['HOUR(TIME(start_time)) as hour', 'COUNT(*) as count'])
            ->where(['website_id' => $this->websiteId])
            ->andWhere(['>=', 'booking_date', $startDate])
            ->groupBy(['HOUR(TIME(start_time))'])
            ->orderBy(['count' => SORT_DESC])
            ->asArray()
            ->one() ?: ['hour' => null, 'count' => 0];

        return $this->formatResult($mostSellingTime, $totalBookings, 'hour');
    }

    private function getMostSellingDay($startDate, $totalBookings): array
    {
        $mostSellingDay = Bookings::find()
            ->select(['DAYNAME(booking_date) as day', 'COUNT(*) as count'])
            ->where(['website_id' => $this->websiteId])
            ->andWhere(['>=', 'booking_date', $startDate])
            ->groupBy(['DAYNAME(booking_date)'])
            ->orderBy(['count' => SORT_DESC])
            ->asArray()
            ->one() ?: ['day' => null, 'count' => 0];

        return $this->formatResult($mostSellingDay, $totalBookings, 'day');
    }

    private function getMostSellingDuration($startDate, $totalBookings): array
    {
        $mostSellingDuration = Bookings::find()
            ->select(['duration_minutes as duration', 'COUNT(*) as count'])
            ->where(['website_id' => $this->websiteId])
            ->andWhere(['>=', 'booking_date', $startDate])
            ->groupBy(['duration_minutes'])
            ->orderBy(['count' => SORT_DESC])
            ->asArray()
            ->one() ?: ['duration' => null, 'count' => 0];

        return $this->formatResult($mostSellingDuration, $totalBookings, 'duration');
    }

    private function getMostSellingService($startDate, $totalBookings): array
    {
        $mostSellingService = Bookings::find()
            ->select(['service_name', 'COUNT(*) as count'])
            ->where(['website_id' => $this->websiteId])
            ->andWhere(['>=', 'booking_date', $startDate])
            ->groupBy(['service_name'])
            ->orderBy(['count' => SORT_DESC])
            ->asArray()
            ->one() ?: ['service_name' => null, 'count' => 0];

        return $this->formatResult($mostSellingService, $totalBookings, 'service_name');
    }

    private function getReturningClients($startDate): array
    {
        $returnClientsData = Bookings::find()
            ->select(['customer_contact', 'customer_name', 'COUNT(*) as count'])
            ->where(['website_id' => $this->websiteId])
            ->andWhere(['>=', 'booking_date', $startDate])
            ->groupBy(['customer_contact', 'customer_name'])
            ->having(['>', 'COUNT(*)', 1])
            ->asArray()
            ->all();

        return array_map(function ($client) {
            return [
                'customerContact' => $client['customer_contact'] ?? '',
                'customerName' => $client['customer_name'] ?? '',
                'bookings' => (int)($client['count'] ?? 0),
            ];
        }, $returnClientsData ?: []);
    }

    private function formatResult($result, $totalBookings, $key): array
    {
        return [
            $key => $result[$key] ?? null,
            'count' => (int)($result['count'] ?? 0),
            'percentage' => $this->calculatePercentageForCategory($result['count'] ?? 0, $totalBookings),
        ];
    }
}

<?php
namespace App\Helpers;

/**
 * DateTimeHelper - Centralized Date/Time Utility for SLA & Aging
 * 
 * All timestamps are generated via PHP (Asia/Jakarta) to avoid
 * timezone mismatch between PHP and SQL Server in production.
 */
class DateTimeHelper {

    /**
     * Get current datetime string in Asia/Jakarta timezone.
     * Use this INSTEAD of SQL GETDATE() for all INSERT/UPDATE operations.
     * 
     * @param string $format DateTime format (default: 'Y-m-d H:i:s')
     * @return string Formatted datetime string
     */
    public static function now($format = 'Y-m-d H:i:s') {
        $tz = new \DateTimeZone('Asia/Jakarta');
        $dt = new \DateTime('now', $tz);
        return $dt->format($format);
    }

    /**
     * Get today's date only (no time).
     * 
     * @return string Date in 'Y-m-d' format
     */
    public static function today() {
        return self::now('Y-m-d');
    }

    /**
     * Add N working days (skip Saturday & Sunday) to a given date.
     * Used for calculating SLA deadline.
     * 
     * @param string $startDate  Start date (Y-m-d H:i:s or Y-m-d)
     * @param int    $days       Number of working days to add
     * @return string            Deadline datetime (Y-m-d H:i:s)
     */
    public static function addWorkingDays($startDate, $days) {
        $tz = new \DateTimeZone('Asia/Jakarta');
        $date = new \DateTime($startDate, $tz);
        
        $added = 0;
        while ($added < $days) {
            $date->modify('+1 day');
            $dayOfWeek = (int)$date->format('N'); // 1=Mon ... 7=Sun
            if ($dayOfWeek <= 5) { // Monday to Friday
                $added++;
            }
        }
        
        return $date->format('Y-m-d H:i:s');
    }

    /**
     * Calculate the number of working days between two dates.
     * Used for measuring actual SLA consumption.
     * 
     * @param string $startDate Start date
     * @param string $endDate   End date (defaults to now)
     * @return int Number of working days elapsed
     */
    public static function workingDaysBetween($startDate, $endDate = null) {
        $tz = new \DateTimeZone('Asia/Jakarta');
        $start = new \DateTime($startDate, $tz);
        $end = $endDate ? new \DateTime($endDate, $tz) : new \DateTime('now', $tz);
        
        if ($end <= $start) return 0;

        $workingDays = 0;
        $current = clone $start;
        
        while ($current < $end) {
            $current->modify('+1 day');
            $dayOfWeek = (int)$current->format('N');
            if ($dayOfWeek <= 5) {
                $workingDays++;
            }
        }
        
        return $workingDays;
    }

    /**
     * Calculate SLA status for display in UI tables.
     * Returns an associative array with visual info (color, label, etc.)
     * 
     * @param string|null $slaDeadline  The deadline datetime (Y-m-d H:i:s)
     * @param bool        $isOnHold     Whether the ticket is currently paused
     * @return array ['status' => 'safe|warning|overdue|paused', 'label' => '...', 'color' => '...', 'bg' => '...', 'days_remaining' => int]
     */
    public static function getSlaStatus($slaDeadline, $isOnHold = false) {
        // Default: No SLA set
        if (empty($slaDeadline)) {
            return [
                'status' => 'none',
                'label'  => '-',
                'color'  => '#94a3b8',
                'bg'     => '#f1f5f9',
                'icon'   => '',
                'days_remaining' => null
            ];
        }

        // Paused (On Hold)
        if ($isOnHold) {
            return [
                'status' => 'paused',
                'label'  => 'ON HOLD',
                'color'  => '#6366f1',
                'bg'     => '#eef2ff',
                'icon'   => '⏸️',
                'days_remaining' => null
            ];
        }

        $tz = new \DateTimeZone('Asia/Jakarta');
        $now = new \DateTime('now', $tz);
        $deadline = new \DateTime($slaDeadline, $tz);
        
        $diff = $now->diff($deadline);
        $totalHours = ($diff->days * 24) + $diff->h;
        $isOverdue = $now > $deadline;
        
        if ($isOverdue) {
            // OVERDUE (Melewati batas 7 hari kerja)
            $overdueDays = $diff->days;
            $overdueHours = $diff->h;
            $label = 'OVERDUE';
            if ($overdueDays > 0) {
                $label .= " ({$overdueDays}d {$overdueHours}h)";
            } else {
                $label .= " ({$overdueHours}h)";
            }
            return [
                'status' => 'overdue',
                'label'  => $label,
                'color'  => '#dc2626',
                'bg'     => '#fef2f2',
                'icon'   => '🔴',
                'days_remaining' => -$overdueDays
            ];
        }

        // Calculate remaining working days for WARNING threshold
        $remainDays = $diff->days;
        $remainHours = $diff->h;
        
        // WARNING: Remaining <= 4 calendar days (approx. day 3+ of 7-day SLA)
        // This triggers yellow indicator when ~3 working days have been consumed
        if ($remainDays <= 4) {
            $label = "Sisa {$remainDays}d {$remainHours}j";
            if ($remainDays === 0) {
                $label = "Sisa {$remainHours}j {$diff->i}m";
            }
            return [
                'status' => 'warning',
                'label'  => $label,
                'color'  => '#d97706',
                'bg'     => '#fffbeb',
                'icon'   => '🟡',
                'days_remaining' => $remainDays
            ];
        }

        // SAFE (Hari ke-1 dan ke-2)
        $label = "Sisa {$remainDays}d {$remainHours}j";
        return [
            'status' => 'safe',
            'label'  => $label,
            'color'  => '#16a34a',
            'bg'     => '#f0fdf4',
            'icon'   => '🟢',
            'days_remaining' => $remainDays
        ];
    }

    /**
     * Calculate Aging (total calendar days since ticket creation).
     * 
     * @param string $createdAt  The creation datetime
     * @return array ['days' => int, 'label' => string, 'color' => string]
     */
    public static function getAging($createdAt) {
        if (empty($createdAt)) {
            return ['days' => 0, 'label' => '-', 'color' => '#94a3b8'];
        }

        $tz = new \DateTimeZone('Asia/Jakarta');
        $created = new \DateTime($createdAt, $tz);
        $now = new \DateTime('now', $tz);
        
        $diff = $now->diff($created);
        $days = $diff->days;
        
        // Color coding based on age
        if ($days <= 3) {
            $color = '#16a34a'; // Green - Fresh
        } elseif ($days <= 7) {
            $color = '#d97706'; // Amber - Getting old
        } else {
            $color = '#dc2626'; // Red - Very old
        }

        $label = $days . ' Hari';
        if ($days === 0) {
            $hours = $diff->h;
            $label = $hours . ' Jam';
        }

        return [
            'days'  => $days,
            'label' => $label,
            'color' => $color
        ];
    }

    /**
     * Get SLA duration (in working days) for a given role.
     * This is the central config — change values here when management updates SLA policy.
     * 
     * Range: 3-7 working days (as per company agreement).
     * 
     * @param string $role  The role code (SPV, PIC, PROCEDURE)
     * @return int Number of working days
     */
    public static function getSlaDays($role) {
        // SLA Policy: 3-7 Hari Kerja per role (sama rata)
        // Deadline absolut = 7 hari kerja
        // Warning mulai hari ke-3 (sisa <= 4 hari kalendar)
        $slaConfig = [
            'SPV'       => 7,   // Supervisor: 7 working days
            'PIC'       => 7,   // PIC (Executor): 7 working days
            'PROCEDURE' => 7,   // Procedure/QA: 7 working days
            'MAKER'     => 14,  // Maker revision deadline: 14 working days
        ];

        return $slaConfig[strtoupper($role)] ?? 7; // Default 7 days
    }
}

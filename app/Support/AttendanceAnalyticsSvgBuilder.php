<?php

namespace App\Support;

class AttendanceAnalyticsSvgBuilder
{
    public function buildLineChart(array $chart, array $statusMeta): string
    {
        $width = 760;
        $height = 300;
        $paddingLeft = 56;
        $paddingTop = 24;
        $paddingBottom = 48;
        $paddingRight = 28;
        $chartWidth = $width - $paddingLeft - $paddingRight;
        $chartHeight = $height - $paddingTop - $paddingBottom;
        $labels = $chart['labels'];
        $pointCount = max(count($labels), 1);
        $maxValue = max(1, ...array_merge(...array_map(fn (array $dataset) => $dataset['data'], $chart['datasets'])));
        $segments = max($pointCount - 1, 1);

        $svg = [];
        $svg[] = sprintf('<svg xmlns="http://www.w3.org/2000/svg" width="%d" height="%d" viewBox="0 0 %d %d">', $width, $height, $width, $height);
        $svg[] = '<rect width="100%" height="100%" fill="#ffffff" rx="18"/>';

        for ($step = 0; $step <= 4; $step++) {
            $y = $paddingTop + ($chartHeight / 4 * $step);
            $value = (int) round($maxValue - (($maxValue / 4) * $step));
            $svg[] = sprintf('<line x1="%d" y1="%.2f" x2="%d" y2="%.2f" stroke="#E5E7EB" stroke-width="1"/>', $paddingLeft, $y, $width - $paddingRight, $y);
            $svg[] = sprintf('<text x="%d" y="%.2f" font-size="12" fill="#6B7280" text-anchor="end">%d</text>', $paddingLeft - 8, $y + 4, $value);
        }

        foreach ($labels as $index => $label) {
            $x = $paddingLeft + ($chartWidth / $segments * $index);
            $svg[] = sprintf('<text x="%.2f" y="%d" font-size="11" fill="#6B7280" text-anchor="middle">%s</text>', $x, $height - 16, htmlspecialchars($label, ENT_QUOTES, 'UTF-8'));
        }

        foreach ($chart['datasets'] as $dataset) {
            $points = [];

            foreach ($dataset['data'] as $index => $value) {
                $x = $paddingLeft + ($chartWidth / $segments * $index);
                $y = $paddingTop + ($chartHeight - (($value / $maxValue) * $chartHeight));
                $points[] = [$x, $y, $value];
            }

            $path = collect($points)->map(fn (array $point, int $index) => ($index === 0 ? 'M' : 'L').sprintf(' %.2f %.2f', $point[0], $point[1]))->implode(' ');
            $svg[] = sprintf('<path d="%s" fill="none" stroke="%s" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>', $path, $dataset['borderColor']);

            foreach ($points as [$x, $y, $value]) {
                $svg[] = sprintf('<circle cx="%.2f" cy="%.2f" r="4" fill="#ffffff" stroke="%s" stroke-width="2"/>', $x, $y, $dataset['borderColor']);
            }
        }

        $legendX = $paddingLeft;
        $legendY = 14;
        foreach ($chart['datasets'] as $dataset) {
            $svg[] = sprintf('<rect x="%d" y="%d" width="12" height="12" rx="3" fill="%s"/>', $legendX, $legendY, $dataset['borderColor']);
            $svg[] = sprintf('<text x="%d" y="%d" font-size="12" fill="#374151">%s</text>', $legendX + 18, $legendY + 10, htmlspecialchars($dataset['label'], ENT_QUOTES, 'UTF-8'));
            $legendX += 92;
        }

        $svg[] = '</svg>';

        return implode('', $svg);
    }

    public function buildBarChart(array $chart): string
    {
        $width = 760;
        $height = 320;
        $paddingLeft = 56;
        $paddingTop = 24;
        $paddingBottom = 48;
        $paddingRight = 24;
        $chartWidth = $width - $paddingLeft - $paddingRight;
        $chartHeight = $height - $paddingTop - $paddingBottom;
        $labels = $chart['labels'];
        $groupCount = max(count($labels), 1);
        $seriesCount = max(count($chart['datasets']), 1);
        $maxValue = max(1, ...array_merge(...array_map(fn (array $dataset) => $dataset['data'], $chart['datasets'])));
        $groupWidth = $chartWidth / $groupCount;
        $barWidth = max(12, min(24, ($groupWidth - 20) / $seriesCount));

        $svg = [];
        $svg[] = sprintf('<svg xmlns="http://www.w3.org/2000/svg" width="%d" height="%d" viewBox="0 0 %d %d">', $width, $height, $width, $height);
        $svg[] = '<rect width="100%" height="100%" fill="#ffffff" rx="18"/>';

        for ($step = 0; $step <= 4; $step++) {
            $y = $paddingTop + ($chartHeight / 4 * $step);
            $value = (int) round($maxValue - (($maxValue / 4) * $step));
            $svg[] = sprintf('<line x1="%d" y1="%.2f" x2="%d" y2="%.2f" stroke="#E5E7EB" stroke-width="1"/>', $paddingLeft, $y, $width - $paddingRight, $y);
            $svg[] = sprintf('<text x="%d" y="%.2f" font-size="12" fill="#6B7280" text-anchor="end">%d</text>', $paddingLeft - 8, $y + 4, $value);
        }

        foreach ($labels as $groupIndex => $label) {
            $baseX = $paddingLeft + ($groupWidth * $groupIndex) + 10;
            foreach ($chart['datasets'] as $seriesIndex => $dataset) {
                $value = $dataset['data'][$groupIndex] ?? 0;
                $barHeight = $maxValue > 0 ? ($value / $maxValue) * $chartHeight : 0;
                $x = $baseX + ($seriesIndex * ($barWidth + 6));
                $y = $paddingTop + ($chartHeight - $barHeight);
                $svg[] = sprintf('<rect x="%.2f" y="%.2f" width="%.2f" height="%.2f" rx="6" fill="%s"/>', $x, $y, $barWidth, max($barHeight, 1), $dataset['backgroundColor']);
            }

            $svg[] = sprintf('<text x="%.2f" y="%d" font-size="11" fill="#6B7280" text-anchor="middle">%s</text>', $paddingLeft + ($groupWidth * $groupIndex) + ($groupWidth / 2), $height - 16, htmlspecialchars($label, ENT_QUOTES, 'UTF-8'));
        }

        $legendX = $paddingLeft;
        $legendY = 14;
        foreach ($chart['datasets'] as $dataset) {
            $svg[] = sprintf('<rect x="%d" y="%d" width="12" height="12" rx="3" fill="%s"/>', $legendX, $legendY, $dataset['backgroundColor']);
            $svg[] = sprintf('<text x="%d" y="%d" font-size="12" fill="#374151">%s</text>', $legendX + 18, $legendY + 10, htmlspecialchars($dataset['label'], ENT_QUOTES, 'UTF-8'));
            $legendX += 92;
        }

        $svg[] = '</svg>';

        return implode('', $svg);
    }

    public function buildPieChart(array $chart): string
    {
        $labels = array_values($chart['labels']);
        $counts = array_values($chart['counts']);
        $percentages = array_values($chart['percentages']);
        $backgroundColors = array_values($chart['backgroundColor']);
        $width = 420;
        $height = 320;
        $centerX = 140;
        $centerY = 150;
        $radius = 90;
        $total = max(array_sum($counts), 1);
        $angle = -90;
        $svg = [];

        $svg[] = sprintf('<svg xmlns="http://www.w3.org/2000/svg" width="%d" height="%d" viewBox="0 0 %d %d">', $width, $height, $width, $height);
        $svg[] = '<rect width="100%" height="100%" fill="#ffffff" rx="18"/>';

        foreach ($counts as $index => $count) {
            $slice = ($count / $total) * 360;
            $endAngle = $angle + $slice;
            $largeArc = $slice > 180 ? 1 : 0;
            $x1 = $centerX + $radius * cos(deg2rad($angle));
            $y1 = $centerY + $radius * sin(deg2rad($angle));
            $x2 = $centerX + $radius * cos(deg2rad($endAngle));
            $y2 = $centerY + $radius * sin(deg2rad($endAngle));
            $path = sprintf('M %1$.2f %2$.2f L %3$.2f %4$.2f A %5$.2f %5$.2f 0 %6$d 1 %7$.2f %8$.2f Z', $centerX, $centerY, $x1, $y1, $radius, $largeArc, $x2, $y2);
            $svg[] = sprintf('<path d="%s" fill="%s"/>', $path, $backgroundColors[$index]);
            $angle = $endAngle;
        }

        $svg[] = sprintf('<circle cx="%d" cy="%d" r="42" fill="#ffffff"/>', $centerX, $centerY);
        $svg[] = sprintf('<text x="%d" y="%d" font-size="16" font-weight="bold" fill="#111827" text-anchor="middle">%d</text>', $centerX, $centerY - 4, array_sum($counts));
        $svg[] = sprintf('<text x="%d" y="%d" font-size="11" fill="#6B7280" text-anchor="middle">Total</text>', $centerX, $centerY + 16);

        $legendY = 56;
        foreach ($labels as $index => $label) {
            $percentage = $percentages[$index] ?? 0;
            $svg[] = sprintf('<rect x="270" y="%d" width="12" height="12" rx="3" fill="%s"/>', $legendY - 10, $backgroundColors[$index]);
            $svg[] = sprintf('<text x="290" y="%d" font-size="12" fill="#374151">%s: %d (%s%%)</text>', $legendY, htmlspecialchars($label, ENT_QUOTES, 'UTF-8'), $counts[$index], number_format((float) $percentage, 1, ',', '.'));
            $legendY += 28;
        }

        $svg[] = '</svg>';

        return implode('', $svg);
    }
}
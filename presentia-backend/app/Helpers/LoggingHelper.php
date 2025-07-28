<?php

if (!function_exists('log_pretty')) {
    function log_pretty($label, $data) {
        if ($data instanceof \Illuminate\Support\Collection) {
            $data = $data->toArray();
        }
        \Log::info("$label:\n" . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}

if (!function_exists('log_json_pretty')) {
    function log_json_pretty($label, $data) {
        if ($data instanceof \Illuminate\Support\Collection) {
            $data = $data->toArray();
        }
        $entry = [
            'label' => $label,
            'timestamp' => now()->toDateTimeString(),
            'data' => $data,
        ];
        file_put_contents(
            storage_path('logs/debug.json'),
            json_encode($entry, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n",
            FILE_APPEND
        );
    }
}
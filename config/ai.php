<?php

return [

    'vision' => [
        'driver' => env('AI_VISION_DRIVER', 'openai'),
        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'model' => env('OPENAI_VISION_MODEL', 'gpt-5.4'),
        ],
    ],

    'transcription' => [
        'driver' => env('AI_TRANSCRIPTION_DRIVER', 'deepgram'),
        'deepgram' => [
            'api_key' => env('DEEPGRAM_API_KEY'),
            'model' => env('DEEPGRAM_MODEL', 'nova-2-medical'),
            'language' => env('DEEPGRAM_LANGUAGE', 'es'),
        ],
    ],

    'scribe' => [
        'driver' => env('AI_SCRIBE_DRIVER', 'deepseek'),
        'deepseek' => [
            'api_key' => env('DEEPSEEK_API_KEY'),
            'model' => env('DEEPSEEK_MODEL', 'deepseek-chat'),
        ],
        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'model' => env('OPENAI_SCRIBE_MODEL', 'gpt-5.4'),
        ],
    ],

];
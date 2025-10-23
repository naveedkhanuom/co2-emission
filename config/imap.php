<?php

return [

    'default' => env('IMAP_DEFAULT_ACCOUNT', 'zoho'),

    'date_format' => 'd-M-Y',

   'accounts' => [
    'default' => [
        'host'          => 'imap.zoho.com',
        'port'          => 993,
        'encryption'    => 'ssl',
        'validate_cert' => true,
        'username'      => env('ZOHO_EMAIL'),
        'password'      => env('ZOHO_PASSWORD'),
        'protocol'      => 'imap',
    ],


        /*
        'gmail' => [
            'host' => 'imap.gmail.com',
            'port' => 993,
            'encryption' => 'ssl',
            'validate_cert' => true,
            'username' => 'example@gmail.com',
            'password' => 'PASSWORD',
            'authentication' => 'oauth',
        ],
        */
    ],

    'options' => [
        'delimiter'    => '/',
        'fetch'        => \Webklex\PHPIMAP\IMAP::FT_PEEK,
        'sequence'     => \Webklex\PHPIMAP\IMAP::ST_UID,
        'fetch_body'   => true,
        'fetch_flags'  => true,
        'soft_fail'    => false,
        'rfc822'       => true,
        'debug'        => false,
        'uid_cache'    => true,
        'boundary'     => '/boundary=(.*?(?=;)|(.*))/i',
        'message_key'  => 'list',
        'fetch_order'  => 'asc',
        'dispositions' => ['attachment', 'inline'],
        'common_folders' => [
            "root"  => "INBOX",
            "junk"  => "INBOX/Junk",
            "draft" => "INBOX/Drafts",
            "sent"  => "INBOX/Sent",
            "trash" => "INBOX/Trash",
        ],
    ],

    'decoding' => [
        'options' => [
            'header'     => 'utf-8',
            'message'    => 'utf-8',
            'attachment' => 'utf-8',
        ],
        'decoder' => [
            'header'     => \Webklex\PHPIMAP\Decoder\HeaderDecoder::class,
            'message'    => \Webklex\PHPIMAP\Decoder\MessageDecoder::class,
            'attachment' => \Webklex\PHPIMAP\Decoder\AttachmentDecoder::class,
        ]
    ],

    'flags' => ['recent', 'flagged', 'answered', 'deleted', 'seen', 'draft'],

    'events' => [
        "message" => [
            'new'     => \Webklex\IMAP\Events\MessageNewEvent::class,
            'moved'   => \Webklex\IMAP\Events\MessageMovedEvent::class,
            'copied'  => \Webklex\IMAP\Events\MessageCopiedEvent::class,
            'deleted' => \Webklex\IMAP\Events\MessageDeletedEvent::class,
            'restored'=> \Webklex\IMAP\Events\MessageRestoredEvent::class,
        ],
        "folder" => [
            'new'     => \Webklex\IMAP\Events\FolderNewEvent::class,
            'moved'   => \Webklex\IMAP\Events\FolderMovedEvent::class,
            'deleted' => \Webklex\IMAP\Events\FolderDeletedEvent::class,
        ],
        "flag" => [
            'new'     => \Webklex\IMAP\Events\FlagNewEvent::class,
            'deleted' => \Webklex\IMAP\Events\FlagDeletedEvent::class,
        ],
    ],

    'masks' => [
        'message'    => \Webklex\PHPIMAP\Support\Masks\MessageMask::class,
        'attachment' => \Webklex\PHPIMAP\Support\Masks\AttachmentMask::class
    ]
];

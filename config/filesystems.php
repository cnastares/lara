<?php

return [
	
	/*
	|--------------------------------------------------------------------------
	| Default Filesystem Disk
	|--------------------------------------------------------------------------
	|
	| Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
	|
	*/
	
    'default' => env('FILESYSTEM_DISK', 'public'),
	
	/*
	|--------------------------------------------------------------------------
	| Default Cloud Filesystem Disk (Deprecated from Laravel 11.x)
	|--------------------------------------------------------------------------
	|
	| Many applications store files both locally and in the cloud. For this
	| reason, you may specify a default "cloud" driver here. This driver
	| will be bound as the Cloud disk implementation in the container.
	|
	*/
	
    'cloud' => env('FILESYSTEM_CLOUD', 's3'),
	
	/*
	|--------------------------------------------------------------------------
	| Filesystem Disks
	|--------------------------------------------------------------------------
	|
	| Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported Drivers: "local", "ftp", "sftp", "s3", "dropbox"
	|
	*/
	
    'disks' => [
		
		'local' => [
			'driver' => 'local',
			'root'   => storage_path('app'),
			'throw'  => true,
		],
		
		'public' => [
			'driver' 	 => 'local',
			'root' 		 => storage_path('app/public'),
			'url' 		 => env('APP_URL').'/storage',
			'visibility' => 'public',
			'throw'      => false,
		],
		
                'private' => [
                        'driver' => 'local',
                        'root'   => storage_path('app/private'),
                        'throw'  => true,
                ],

                'temp' => [
                        'driver' => 'local',
                        'root'   => storage_path('app/temp'),
                ],
		
		//---
		
        // Used for Admin -> Log
        'storage' => [
            'driver' => 'local',
            'root'   => storage_path(),
			'throw'  => true,
        ],

        // Used for Admin -> Backup
        'backups' => [
            'driver' => 'local',
            'root'   => storage_path('backups'), // that's where your backups are stored by default: storage/backups
			'throw'  => true,
        ],
		
		//---
		
		'ftp' => [
			'driver'   => 'ftp',
			'host'     => env('FTP_HOST'),
			'username' => env('FTP_USERNAME'),
			'password' => env('FTP_PASSWORD'),
			'port'     => env('FTP_PORT', 21),
			'root'     => env('FTP_ROOT', ''),
			'passive'  => env('FTP_PASSIVE', true),
			'ssl'      => env('FTP_SSL', true),
			'timeout'  => env('FTP_TIMEOUT', 30),
			'throw'    => env('FTP_THROW', false),
		],
		
		'sftp' => [
			'driver'          => 'sftp',
			'host' 	          => env('SFTP_HOST'),
			'username'        => env('SFTP_USERNAME'),
			'password'        => env('SFTP_PASSWORD'), // Or SSH Encryption Password
			'privateKey'      => env('SFTP_SSH_PRIVATE_KEY'), // '/path/to/privateKey'
			'hostFingerprint' => env('SFTP_HOST_FINGERPRINT'),
			'maxTries'        => env('SFTP_MAX_TRIES', 4),
			'passphrase'      => env('SFTP_PASSPHRASE'),
			'port'            => env('SFTP_PORT', 22),
			'root'            => env('SFTP_ROOT', ''),
			'timeout'         => env('SFTP_TIMEOUT', 30),
			'throw'           => env('SFTP_THROW', false),
		],
		
		'minio' => [
			'driver'   => 's3',
			'key'      => env('MINIO_KEY'),
			'secret'   => env('MINIO_SECRET'),
			'region'   => env('MINIO_REGION'),
			'bucket'   => env('MINIO_BUCKET'),
			'url'	   => env('MINIO_URL', ''),
			'endpoint' => env('MINIO_ENDPOINT', 'http://127.0.0.1:9000'),
			'use_path_style_endpoint' => env('MINIO_USE_PATH_STYLE_ENDPOINT', true),
			'throw'    => env('MINIO_THROW', false),
		],
		
		's3' => [
			'driver'   => 's3',
			'key' 	   => env('AWS_ACCESS_KEY_ID'),
			'secret'   => env('AWS_SECRET_ACCESS_KEY'),
			'region'   => env('AWS_DEFAULT_REGION'),
			'bucket'   => env('AWS_BUCKET'),
			'url'	   => env('AWS_URL', ''),
			'endpoint' => env('AWS_ENDPOINT'),
			'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
			'throw'    => env('AWS_THROW', false),
		],
		
		'digitalocean' => [
			'driver'   => 's3',
			'key'      => env('DIGITALOCEAN_KEY'),
			'secret'   => env('DIGITALOCEAN_SECRET'),
			'region'   => env('DIGITALOCEAN_REGION'),
			'bucket'   => env('DIGITALOCEAN_BUCKET'),
			'url'      => env('DIGITALOCEAN_URL'),
			'endpoint' => env('DIGITALOCEAN_ENDPOINT'),
			'use_path_style_endpoint' => env('DIGITALOCEAN_USE_PATH_STYLE_ENDPOINT', false),
			'folder'       => env('DIGITALOCEAN_FOLDER'),
			'cdn_endpoint' => env('DIGITALOCEAN_CDN_ENDPOINT'),
			'throw'        => env('DIGITALOCEAN_THROW', false),
		],
		
		'dropbox' => [
			'driver'              => 'dropbox',
			'root'                => env('DROPBOX_ROOT', '/'),
			'authorization_token' => env('DROPBOX_AUTHORIZATION_TOKEN', ''),
			'throw'               => env('DROPBOX_THROW', false),
		],
		
    ],
	
	/*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */
	
	'links' => [
		public_path('storage') => storage_path('app/public'),
	],
	
];

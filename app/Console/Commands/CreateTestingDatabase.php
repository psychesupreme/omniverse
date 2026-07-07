<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PDO;
use PDOException;

class CreateTestingDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:create-testing';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create the testing database if it does not exist';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $database = 'omniroute_testing';
        $config = config('database.connections.pgsql');

        if (!$config) {
            $this->error('Database connection pgsql not found in configuration.');
            return 1;
        }

        try {
            // Connect to the default 'postgres' database to create the testing DB
            $pdo = new PDO(
                "pgsql:host={$config['host']};port={$config['port']};dbname=postgres",
                $config['username'],
                $config['password']
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $query = $pdo->query("SELECT 1 FROM pg_database WHERE datname = '{$database}'");
            $exists = $query->fetch();

            if (!$exists) {
                $pdo->exec("CREATE DATABASE \"{$database}\"");
                $this->info("Database '{$database}' created successfully.");
            } else {
                $this->info("Database '{$database}' already exists.");
            }

            return 0;
        } catch (PDOException $e) {
            $this->error("Failed to connect or create database: {$e->getMessage()}");
            return 1;
        }
    }
}

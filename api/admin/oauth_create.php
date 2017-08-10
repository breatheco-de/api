<?php

    require_once('../../vendor/autoload.php');
    require_once('../dependencies.php');

    $schema = $app->db->getSchemaBuilder();
    $app->db->listen(function($sql) {
        var_dump($sql);
    });
    $schema->disableForeignKeyConstraints();

echo "Creating tables for OAuth 2.0 credentials... \n";
$schema->dropIfExists('oauth_clients');
$schema->dropIfExists('oauth_access_tokens');
$schema->dropIfExists('oauth_authorization_codes');
$schema->dropIfExists('oauth_refresh_tokens');
$schema->dropIfExists('oauth_users');
$schema->dropIfExists('oauth_scopes');
$schema->dropIfExists('oauth_jwt');
if(!$schema->hasTable('oauth_clients'))
{
    $createQuery = <<<SCHEMA
CREATE TABLE oauth_clients (client_id VARCHAR(80) NOT NULL, client_secret VARCHAR(80) NOT NULL, redirect_uri VARCHAR(2000) NOT NULL, grant_types VARCHAR(80), scope VARCHAR(100), user_id VARCHAR(80), CONSTRAINT client_id_pk PRIMARY KEY (client_id));
CREATE TABLE oauth_access_tokens (access_token VARCHAR(40) NOT NULL, client_id VARCHAR(80) NOT NULL, user_id VARCHAR(255), expires TIMESTAMP NOT NULL, scope VARCHAR(2000), CONSTRAINT access_token_pk PRIMARY KEY (access_token));
CREATE TABLE oauth_authorization_codes (authorization_code VARCHAR(40) NOT NULL, client_id VARCHAR(80) NOT NULL, user_id VARCHAR(255), redirect_uri VARCHAR(2000), expires TIMESTAMP NOT NULL, scope VARCHAR(2000), CONSTRAINT auth_code_pk PRIMARY KEY (authorization_code));
CREATE TABLE oauth_refresh_tokens (refresh_token VARCHAR(40) NOT NULL, client_id VARCHAR(80) NOT NULL, user_id VARCHAR(255), expires TIMESTAMP NOT NULL, scope VARCHAR(2000), CONSTRAINT refresh_token_pk PRIMARY KEY (refresh_token));
CREATE TABLE oauth_users (username VARCHAR(255) NOT NULL, password VARCHAR(2000), first_name VARCHAR(255), last_name VARCHAR(255), CONSTRAINT username_pk PRIMARY KEY (username));
CREATE TABLE oauth_scopes (scope TEXT, is_default BOOLEAN);
CREATE TABLE oauth_jwt (client_id VARCHAR(80) NOT NULL, subject VARCHAR(80), public_key VARCHAR(2000), CONSTRAINT client_id_pk PRIMARY KEY (client_id));
SCHEMA;

	foreach (explode("\n", $createQuery) as $statement) {
		$app->db->statement($statement);
	}
	
	$app->db->table('oauth_clients')->insert(array(
			'client_id' => "alesanchezr",
			'client_secret' => "714bfa43e7c312be999d0afea89148e7",
			'redirect_uri' => "http://fake/",
			'scope' => "sync_data read_basic_info read_talent_tree student_assignments teacher_assignments super_admin"
		));
		
	$app->db->table('oauth_clients')->insert(array(
			'client_id' => "ogarcia",
			'client_secret' => "8ca0854a441cc4c201f925d6bfb36dafa48829c4",
			'redirect_uri' => "http://fake/",
			'scope' => "read_basic_info read_talent_tree student_assignments teacher_assignments super_admin"
		));
		
	$app->db->table('oauth_clients')->insert(array(
			'client_id' => "nbernal",
			'client_secret' => "8ca0854a441cc4c201f925d6bfb36dafa48829c5",
			'redirect_uri' => "http://fake/",
			'scope' => "read_basic_info read_talent_tree student_assignments teacher_assignments super_admin"
		));
		
}

$schema->enableForeignKeyConstraints();
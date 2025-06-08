CREATE TABLE IF NOT EXISTS "migrations"(
  "id" integer primary key autoincrement not null,
  "migration" varchar not null,
  "batch" integer not null
);
CREATE TABLE IF NOT EXISTS "users"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "email" varchar not null,
  "email_verified_at" datetime,
  "password" varchar not null,
  "remember_token" varchar,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "users_email_unique" on "users"("email");
CREATE TABLE IF NOT EXISTS "password_reset_tokens"(
  "email" varchar not null,
  "token" varchar not null,
  "created_at" datetime,
  primary key("email")
);
CREATE TABLE IF NOT EXISTS "sessions"(
  "id" varchar not null,
  "user_id" integer,
  "ip_address" varchar,
  "user_agent" text,
  "payload" text not null,
  "last_activity" integer not null,
  primary key("id")
);
CREATE INDEX "sessions_user_id_index" on "sessions"("user_id");
CREATE INDEX "sessions_last_activity_index" on "sessions"("last_activity");
CREATE TABLE IF NOT EXISTS "cache"(
  "key" varchar not null,
  "value" text not null,
  "expiration" integer not null,
  primary key("key")
);
CREATE TABLE IF NOT EXISTS "cache_locks"(
  "key" varchar not null,
  "owner" varchar not null,
  "expiration" integer not null,
  primary key("key")
);
CREATE TABLE IF NOT EXISTS "jobs"(
  "id" integer primary key autoincrement not null,
  "queue" varchar not null,
  "payload" text not null,
  "attempts" integer not null,
  "reserved_at" integer,
  "available_at" integer not null,
  "created_at" integer not null
);
CREATE INDEX "jobs_queue_index" on "jobs"("queue");
CREATE TABLE IF NOT EXISTS "job_batches"(
  "id" varchar not null,
  "name" varchar not null,
  "total_jobs" integer not null,
  "pending_jobs" integer not null,
  "failed_jobs" integer not null,
  "failed_job_ids" text not null,
  "options" text,
  "cancelled_at" integer,
  "created_at" integer not null,
  "finished_at" integer,
  primary key("id")
);
CREATE TABLE IF NOT EXISTS "failed_jobs"(
  "id" integer primary key autoincrement not null,
  "uuid" varchar not null,
  "connection" text not null,
  "queue" text not null,
  "payload" text not null,
  "exception" text not null,
  "failed_at" datetime not null default CURRENT_TIMESTAMP
);
CREATE UNIQUE INDEX "failed_jobs_uuid_unique" on "failed_jobs"("uuid");
CREATE TABLE IF NOT EXISTS "prompts"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "title" varchar not null,
  "description" text,
  "content" text not null,
  "category" varchar not null,
  "tags" text,
  "source_type" varchar check("source_type" in('manual', 'fabric', 'github')) not null default 'manual',
  "source_url" varchar,
  "estimated_tokens" integer not null default '0',
  "synced_at" datetime,
  "is_active" tinyint(1) not null default '1',
  "is_public" tinyint(1) not null default '1',
  "checksum" varchar,
  "created_by" integer,
  "metadata" text,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE INDEX "prompts_title_index" on "prompts"("title");
CREATE INDEX "prompts_description_index" on "prompts"("description");
CREATE INDEX "prompts_source_type_is_active_index" on "prompts"(
  "source_type",
  "is_active"
);
CREATE INDEX "prompts_category_is_active_index" on "prompts"(
  "category",
  "is_active"
);
CREATE UNIQUE INDEX "prompts_name_source_type_unique" on "prompts"(
  "name",
  "source_type"
);
CREATE INDEX "prompts_name_index" on "prompts"("name");
CREATE INDEX "prompts_category_index" on "prompts"("category");
CREATE INDEX "prompts_source_type_index" on "prompts"("source_type");
CREATE INDEX "prompts_is_active_index" on "prompts"("is_active");
CREATE INDEX "prompts_is_public_index" on "prompts"("is_public");
CREATE TABLE IF NOT EXISTS "compositions"(
  "id" integer primary key autoincrement not null,
  "prompt_id" integer not null,
  "input_content" text,
  "composed_content" text not null,
  "metadata" text,
  "tokens_used" integer not null default '0',
  "compose_time_ms" float not null default '0',
  "client_info" varchar not null default 'unknown',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("prompt_id") references "prompts"("id") on delete cascade
);
CREATE INDEX "compositions_prompt_id_created_at_index" on "compositions"(
  "prompt_id",
  "created_at"
);
CREATE TABLE IF NOT EXISTS "pattern_executions"(
  "id" integer primary key autoincrement not null,
  "fabric_pattern_id" integer not null,
  "input_content" text,
  "output_content" text,
  "metadata" text,
  "tokens_used" integer,
  "execution_time_ms" float,
  "client_info" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("fabric_pattern_id") references "fabric_patterns"("id") on delete cascade
);
CREATE INDEX "pattern_executions_fabric_pattern_id_created_at_index" on "pattern_executions"(
  "fabric_pattern_id",
  "created_at"
);
CREATE INDEX "pattern_executions_created_at_index" on "pattern_executions"(
  "created_at"
);
CREATE TABLE IF NOT EXISTS "prompt_sources"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "type" varchar not null,
  "repository_url" text,
  "branch" varchar not null default 'main',
  "path_pattern" varchar not null default '**/*.md',
  "file_pattern" varchar not null default 'system.md',
  "is_active" tinyint(1) not null default '1',
  "auto_sync" tinyint(1) not null default '1',
  "last_synced_at" datetime,
  "sync_status" varchar not null default 'pending',
  "sync_error" text,
  "metadata" text,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "prompt_sources_name_unique" on "prompt_sources"("name");

INSERT INTO migrations VALUES(1,'0001_01_01_000000_create_users_table',1);
INSERT INTO migrations VALUES(2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO migrations VALUES(3,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO migrations VALUES(4,'2024_01_01_000001_create_prompts_table',1);
INSERT INTO migrations VALUES(5,'2025_05_30_192936_create_pattern_executions_table',1);
INSERT INTO migrations VALUES(6,'2025_06_06_224343_create_prompt_sources_table',2);

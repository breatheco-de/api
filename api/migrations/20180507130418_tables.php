<?php


use Migrations\Migration;

class Tables extends Migration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     * The following commands can be used in this method and Phinx will
     * automatically reverse them when rolling back:
     *
     *    createTable
     *    renameTable
     *    addColumn
     *    renameColumn
     *    addIndex
     *    addForeignKey
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */

    public function up(){

        $this->schema->disableForeignKeyConstraints();

        if(!$this->schema->hasTable('badges')){
            $this->schema->create('badges', function($table) {
                $table->engine = 'InnoDB';
                $table->bigIncrements('id');
                $table->string('slug', 200)->unique();
                $table->string('name', 200);
                $table->string('icon', 255);
                $table->integer('points_to_achieve');
                $table->text('description');
                $table->string('technologies', 200);
                $table->timestamps();

                $table->index('slug');
            });
        }

        if(!$this->schema->hasTable('users')){
            $this->schema->create('users', function($table) {
                $table->engine = 'InnoDB';
                $table->bigIncrements('id');
                $table->integer('wp_id')->unique()->nullable();
                $table->string('avatar_url', 255)->nullable()->default(null);
                $table->string('bio', 255)->nullable();
                $table->text('settings')->nullable();
                $table->string('full_name', 200)->nullable()->default(null);
                $table->string('first_name', 200)->nullable()->default(null);
                $table->string('last_name', 200)->nullable()->default(null);
                $table->string('type', 20);
                $table->string('username', 200)->unique();
                $table->unsignedBigInteger('parent_location_id')->nullable()->default(null);
                $table->timestamps();

                $table->index('wp_id');
                $table->foreign('parent_location_id')->references('id')->on('locations')->onDelete('cascade');
            });
        }

        if(!$this->schema->hasTable('device')){
            $this->schema->create('device', function($table) {
                $table->engine = 'InnoDB';
                $table->unsignedBigInteger('user_id');
                $table->string('token', 255);
                $table->timestamps();

                $table->primary('token');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }

        if(!$this->schema->hasTable('students')){
            $this->schema->create('students', function($table) {
                $table->engine = 'InnoDB';
                $table->unsignedBigInteger('user_id');
                $table->integer('total_points')->nullable()->default(0);
                $table->boolean('show_tutorial')->default(true);
                $table->boolean('seeking_job')->default(false);
                $table->string('phone', 25)->nullable()->default(null);
                $table->boolean('found_job')->default(false);
                $table->enum('language', ['en', 'es'])->default('en');
                $table->date('dropout_date')->nullable()->default(null);
                $table->timestamps();
                $table->enum('financial_status', ['fully_paid', 'up_to_date', 'late', 'uknown'])->default('uknown');
                $table->enum('status', ['currently_active', 'under_review', 'blocked', 'postponed', 'studies_finished', 'student_dropped'])->default('currently_active');
                $table->primary('user_id');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }

        if(!$this->schema->hasTable('cohorts')){
            $this->schema->create('cohorts', function($table) {
                $table->engine = 'InnoDB';
                $table->bigIncrements('id');
                $table->string('slug', 200)->unique();
                $table->string('name', 200);
                $table->date('kickoff_date')->nullable();
                $table->date('ending_date')->nullable();
                $table->integer('current_day')->default(0);
                $table->string('streaming_slug', 50)->nullable();
                $table->string('syllabus_slug', 150)->nullable()->default(null);
                $table->unsignedBigInteger('location_id');
                $table->unsignedBigInteger('profile_id');
                $table->string('stage', 50);//['not-started', 'on-prework', 'on-course','on-final-project','finished']
                $table->string('language', 2);//['es','en']
                $table->string('slack_url', 200);
                $table->string('meeting_url', 200)->nullable();
                $table->timestamps();

                $table->foreign('location_id')->references('id')->on('locations')->onDelete('cascade');
                $table->foreign('profile_id')->references('id')->on('profiles')->onDelete('cascade');
            });
        }

        if(!$this->schema->hasTable('teachers')){
            $this->schema->create('teachers', function($table) {
                    $table->engine = 'InnoDB';
                    $table->unsignedBigInteger('user_id');
                    $table->timestamps();

                    $table->primary('user_id');
                    $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                });
        }

        if(!$this->schema->hasTable('cohort_teacher')){
            $this->schema->create('cohort_teacher', function($table) {
                $table->engine = 'InnoDB';
                $table->unsignedBigInteger('teacher_user_id');//->primary();
                $table->unsignedBigInteger('cohort_id');//->primary();
                $table->boolean('is_instructor')->default(false);//->primary();
                $table->timestamps();

                $table->foreign('teacher_user_id')->references('user_id')->on('teachers')->onDelete('cascade');
                $table->foreign('cohort_id')->references('id')->on('cohorts')->onDelete('cascade');
            });
        }

        if(!$this->schema->hasTable('cohort_student')){
            $this->schema->create('cohort_student', function($table) {
                $table->engine = 'InnoDB';
                $table->unsignedBigInteger('student_user_id');//->primary();
                $table->unsignedBigInteger('cohort_id');//->primary();
                $table->timestamps();

                $table->foreign('student_user_id')->references('user_id')->on('students')->onDelete('cascade');
                $table->foreign('cohort_id')->references('id')->on('cohorts')->onDelete('cascade');
            });
        }

        if(!$this->schema->hasTable('locations')){
            $this->schema->create('locations', function($table) {
                $table->engine = 'InnoDB';
                $table->bigIncrements('id');
                $table->string('slug', 200)->unique();
                $table->string('name', 200);
                $table->string('country', 200);
                $table->string('address', 200);
                $table->text('logistical_information')->nullable();
                $table->timestamps();

            });
        }

        if(!$this->schema->hasTable('badge_student')){
            $this->schema->create('badge_student', function($table) {
                $table->engine = 'InnoDB';
                $table->unsignedBigInteger('student_user_id');//->primary();
                $table->unsignedBigInteger('badge_id');//->primary();
                $table->unsignedBigInteger('points_acumulated')->default(0);//->primary();
                $table->boolean('is_achieved')->default(false);//->primary();
                $table->timestamps();

                $table->foreign('student_user_id')->references('user_id')->on('students')->onDelete('cascade');
                $table->foreign('badge_id')->references('id')->on('badges')->onDelete('cascade');
            });
        }

        if(!$this->schema->hasTable('activities')){
            $this->schema->create('activities', function($table) {
                $table->engine = 'InnoDB';
                $table->bigIncrements('id');
                $table->unsignedBigInteger('student_user_id');
                $table->unsignedBigInteger('badge_id');
                $table->string('type', 60); //['project', 'quiz', 'challenge', 'teacher_reward']
                $table->string('name', 255);
                $table->text('description');
                $table->integer('points_earned');
                $table->timestamps();

                $table->foreign('student_user_id')->references('user_id')->on('students')->onDelete('cascade');
                $table->foreign('badge_id')->references('id')->on('badges')->onDelete('cascade');;
            });
        }

        if(!$this->schema->hasTable('specialties')){
            $this->schema->create('specialties', function($table) {
                $table->engine = 'InnoDB';
                $table->bigIncrements('id');
                $table->string('slug', 200)->unique();
                $table->string('name', 255);
                $table->string('icon', 255)->nullable();
                $table->text('description');
                $table->integer('points_to_achieve');
                $table->timestamps();

            });
        }

        if(!$this->schema->hasTable('student_specialty')){
            $this->schema->create('student_specialty', function($table) {
                $table->engine = 'InnoDB';
                $table->unsignedBigInteger('student_user_id');
                $table->unsignedBigInteger('specialty_id', 200)->unique();
                $table->timestamps();

                $table->foreign('student_user_id')->references('user_id')->on('students')->onDelete('cascade');;
                $table->foreign('specialty_id')->references('id')->on('specialties')->onDelete('cascade');;
            });
        }

        if(!$this->schema->hasTable('requierments')){
            $this->schema->create('requierments', function($table) {
                $table->engine = 'InnoDB';
                $table->unsignedBigInteger('specialty_id');
                $table->unsignedBigInteger('badge_id');
                $table->integer('points_to_complete');
                $table->timestamps();

                $table->foreign('specialty_id')->references('id')->on('specialties')->onDelete('cascade');
                $table->foreign('badge_id')->references('id')->on('badges')->onDelete('cascade');
            });
        }

        if(!$this->schema->hasTable('profiles')){
            $this->schema->create('profiles', function($table) {
                $table->engine = 'InnoDB';
                $table->bigIncrements('id');
                $table->string('slug', 200)->unique();
                $table->string('name', 255);
                $table->string('logo', 255)->nullable();
                $table->integer('duration_in_hours')->nullable()->default(0);
                $table->integer('duration_in_days')->nullable()->default(0);
                $table->integer('week_hours')->nullable();
                $table->text('description');
                $table->timestamps();

            });
        }

        if(!$this->schema->hasTable('profile_specialty')){
            $this->schema->create('profile_specialty', function($table) {
                $table->engine = 'InnoDB';
                $table->unsignedBigInteger('profile_id');
                $table->unsignedBigInteger('specialty_id');
                $table->timestamps();

                $table->foreign('profile_id')->references('id')->on('profiles')->onDelete('cascade');
                $table->foreign('specialty_id')->references('id')->on('specialties')->onDelete('cascade');
            });
        }

        $this->schema->enableForeignKeyConstraints();
    }

    public function down(){
        if($this->schema->hasTable('users')) $app->db->table('users')->truncate();
        if($this->schema->hasTable('students')) $app->db->table('students')->truncate();
        if($this->schema->hasTable('cohorts')) $app->db->table('cohorts')->truncate();
        if($this->schema->hasTable('teachers')) $app->db->table('teachers')->truncate();
        if($this->schema->hasTable('cohort_teacher')) $app->db->table('cohort_teacher')->truncate();
        if($this->schema->hasTable('cohort_student')) $app->db->table('cohort_student')->truncate();
        if($this->schema->hasTable('locations')) $app->db->table('locations')->truncate();
        if($this->schema->hasTable('badges')) $app->db->table('badges')->truncate();
        if($this->schema->hasTable('badge_student')) $app->db->table('badge_student')->truncate();
        if($this->schema->hasTable('activities')) $app->db->table('activities')->truncate();
        if($this->schema->hasTable('specialties')) $app->db->table('specialties')->truncate();
        if($this->schema->hasTable('student_specialty')) $app->db->table('student_specialty')->truncate();
        if($this->schema->hasTable('requierments')) $app->db->table('requierments')->truncate();
        if($this->schema->hasTable('profiles')) $app->db->table('profiles')->truncate();
        if($this->schema->hasTable('profile_specialty')) $app->db->table('profile_specialty')->truncate();

        $this->schema->dropIfExists('badges');
        $this->schema->dropIfExists('users');
        $this->schema->dropIfExists('students');
        $this->schema->dropIfExists('cohorts');
        $this->schema->dropIfExists('teachers');
        $this->schema->dropIfExists('cohort_teacher');
        $this->schema->dropIfExists('cohort_student');
        $this->schema->dropIfExists('locations');
        $this->schema->dropIfExists('badge_student');
        $this->schema->dropIfExists('activities');
        $this->schema->dropIfExists('specialties');
        $this->schema->dropIfExists('student_specialty');
        $this->schema->dropIfExists('requierments');
        $this->schema->dropIfExists('profiles');
        $this->schema->dropIfExists('profile_specialty');
    }
}

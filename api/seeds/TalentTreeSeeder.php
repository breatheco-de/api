<?php

use Migrations\Seeder;

class TalentTreeSeeder extends Seeder
{
    /**
     * Run Method.
     *
     * Write your database seeder using this method.
     *
     * More information on writing seeders is available here:
     * http://docs.phinx.org/en/latest/seeding.html
     */
    public function run()
    {
        $badge1 = new Badge();
        $badge1->slug = 'clean-coding';
        $badge1->name = 'Clean Coding';
        $badge1->icon = 'http://www.icon.png';
        $badge1->points_to_achieve = 100;
        $badge1->description = 'Your code looks amazing!';
        $badge1->technologies = 'HTML, CSS, PHP, Python';
        $badge1->save();

        $badge2 = new Badge();
        $badge2->slug = 'data-architecht';
        $badge2->name = 'Data Architecht';
        $badge2->icon = 'http://www.icon.png';
        $badge2->points_to_achieve = 100;
        $badge2->description = 'You are very good doing architectures!!';
        $badge2->technologies = 'ReactJS, Webpack';
        $badge2->save();
        
        $profile1 = new Profile();
        $profile1->slug = 'full-stack';
        $profile1->name = 'Full Stack Developer';
        $profile1->description = 'Become a real developer';
        $profile1->save();
        
        $specialty = new Specialty();
        $specialty->slug = 'data-master';
        $specialty->name = 'Data Master';
        $specialty->points_to_achieve = 100;
        $specialty->description = 'Get to know the business of data';
        $specialty->icon = 'http://www.icon.png';
        $specialty->save();
        $specialty->badges()->attach([$badge1->id, $badge2->id]);
        $specialty->profiles()->attach($profile1);
        $specialty->save();
        
        $specialty2 = new Specialty();
        $specialty2->slug = 'front-end';
        $specialty2->name = 'Front End';
        $specialty2->icon = 'http://www.icon.png';
        $specialty2->points_to_achieve = 100;
        $specialty2->description = 'Render beautiful interactive apps';
        $specialty2->save();
        $specialty2->badges()->attach([$badge1->id, $badge2->id]);
        $specialty2->profiles()->attach($profile1);
        $specialty2->save();

    }
}

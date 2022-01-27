<?php
    namespace App\Utility;

    class Time {


        /**
         * Gets the current used for the @createdAt variable during a post request
         * @return DateTime datetime format need by the database
         */
        public static function getCurrentTime() {

            return strftime("%Y-%m-%d %H:%M:%S", time());
        }



    }
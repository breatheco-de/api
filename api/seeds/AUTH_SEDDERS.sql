INSERT INTO `oauth_clients` (`client_id`, `client_secret`, `redirect_uri`, `grant_types`, `scope`, `user_id`) VALUES
('alesanchezr', '714bfa43e7c312be999d0afea89148e7', 'http://fake/', NULL, 'sync_data read_basic_info read_talent_tree student_assignments teacher_assignments super_admin crud_student user_profile student_tasks crud_cohort crud_student update_cohort_current_day', NULL),
('nbernal', '8ca0854a441cc4c201f925d6bfb36dafa48829c5', 'http://fake/', NULL, 'sync_data read_basic_info crud_student crud_cohort user_profile update_cohort_current_day read_talen', NULL);



INSERT INTO `oauth_users` (`username`, `password`, `first_name`, `last_name`) VALUES
('aalejo@gmail.com', '$P$BPnHMn6jZzkT52nEy8.Kdii3z3c4g91', NULL, NULL),
('a@4geeks.co', '$P$BPnHMn6jZzkT52nEy8.Kdii3z3c4g91', NULL, NULL);

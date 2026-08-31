<?php

// Creates the applications table if it does not exist
function ensure_applications_table($pdo) {
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS team_applications (
                application_id INT PRIMARY KEY AUTO_INCREMENT,
                team_id INT NOT NULL,
                student_id INT NOT NULL,
                message TEXT NOT NULL,
                match_score INT NOT NULL DEFAULT 0,
                status ENUM('pending','accepted','rejected') DEFAULT 'pending',
                applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (team_id) REFERENCES team_posts(team_id)
                    ON DELETE CASCADE,
                FOREIGN KEY (student_id) REFERENCES users(user_id)
                    ON DELETE CASCADE
            )
        ");
    } catch (PDOException $e) {
        // Ignore table creation errors
    }
}


// Converts comma-separated values into a clean lowercase array
function tokenize_list($string) {
    if (empty($string)) {
        return [];
    }

    $items = explode(',', $string);
    $result = [];

    foreach ($items as $item) {
        $item = trim(mb_strtolower($item));

        if ($item !== '') {
            $result[] = $item;
        }
    }

    return array_unique($result);
}


// Calculates compatibility between a student and a research post
function calculate_match_score($profile, $post) {

    if (!$profile) {
        return [
            'score' => 0,
            'badge_class' => 'match-low',
            'level' => 'Profile Incomplete',
            'has_profile' => false,
            'breakdown' => []
        ];
    }

    // 1. Skills - 25 points
    $required = tokenize_list($post['required_skills'] ?? '');
    $skills = tokenize_list($profile['skills'] ?? '');

    $matched = 0;

    foreach ($required as $req) {
        foreach ($skills as $skill) {
            if ($req === $skill ||
                strpos($skill, $req) !== false ||
                strpos($req, $skill) !== false) {
                $matched++;
                break;
            }
        }
    }

    $skill_score = empty($required)
        ? 25
        : round(25 * $matched / count($required));


    // 2. Domain & Tags - 20 points
    $user_domains = tokenize_list($profile['preferred_domains'] ?? '');
    $post_domain = mb_strtolower(trim($post['domain'] ?? ''));
    $post_tags = tokenize_list($post['tags'] ?? '');

    $domain_score = 0;

    foreach ($user_domains as $domain) {
        if ($domain === $post_domain ||
            strpos($domain, $post_domain) !== false ||
            strpos($post_domain, $domain) !== false) {
            $domain_score = 12;
            break;
        }
    }

    if (empty($post_domain)) {
        $domain_score = 12;
    }

    $all_interests = array_merge($user_domains, $skills);
    $tag_matches = 0;

    foreach ($post_tags as $tag) {
        foreach ($all_interests as $interest) {
            if ($tag === $interest ||
                strpos($interest, $tag) !== false ||
                strpos($tag, $interest) !== false) {
                $tag_matches++;
                break;
            }
        }
    }

    $tag_score = empty($post_tags)
        ? 8
        : round(8 * $tag_matches / count($post_tags));

    $domain_score = min(20, $domain_score + $tag_score);


    // 3. CGPA - 20 points
    $required_cgpa = (float)($post['minimum_cgpa'] ?? 0);
    $user_cgpa = (float)($profile['cgpa'] ?? 0);

    $cgpa_score = ($required_cgpa <= 0 || $user_cgpa >= $required_cgpa)
        ? 20
        : 0;


    // 4. Semester - 15 points
    $eligible = tokenize_list($post['eligible_semesters'] ?? '');
    $semester = (string)($profile['semester'] ?? '');

    $semester_score = 15;

    if (!empty($eligible)) {
        $semester_score = in_array($semester, $eligible) ? 15 : 0;
    }


    // 5. Supervisor - 10 points
    $post_supervisor = mb_strtolower(trim($post['preferred_supervisor'] ?? ''));
    $user_supervisor = mb_strtolower(trim($profile['preferred_supervisor'] ?? ''));

    if (empty($post_supervisor) || empty($user_supervisor)) {
        $supervisor_score = 5;
    } elseif ($post_supervisor === $user_supervisor) {
        $supervisor_score = 10;
    } else {
        $supervisor_score = 0;
    }


    // 6. Availability - 10 points
    $availability = $profile['availability'] ?? 'Available';

    if ($availability === 'Available') {
        $availability_score = 10;
    } elseif ($availability === 'Partially Available') {
        $availability_score = 6;
    } else {
        $availability_score = 0;
    }


    // Total
    $total = $skill_score +
             $domain_score +
             $cgpa_score +
             $semester_score +
             $supervisor_score +
             $availability_score;

    // Match level
    if ($total >= 75) {
        $level = 'Strong Match';
        $badge = 'match-high';
    } elseif ($total >= 50) {
        $level = 'Moderate Match';
        $badge = 'match-med';
    } else {
        $level = 'Low Match';
        $badge = 'match-low';
    }

    return [
        'score' => $total,
        'badge_class' => $badge,
        'level' => $level,
        'has_profile' => true,

        'breakdown' => [
            'skills' => [
                'name' => 'Skills',
                'score' => $skill_score,
                'max' => 25
            ],
            'domain' => [
                'name' => 'Domain & Tags',
                'score' => $domain_score,
                'max' => 20
            ],
            'cgpa' => [
                'name' => 'CGPA',
                'score' => $cgpa_score,
                'max' => 20
            ],
            'semester' => [
                'name' => 'Semester',
                'score' => $semester_score,
                'max' => 15
            ],
            'supervisor' => [
                'name' => 'Supervisor',
                'score' => $supervisor_score,
                'max' => 10
            ],
            'availability' => [
                'name' => 'Availability',
                'score' => $availability_score,
                'max' => 10
            ]
        ]
    ];
}

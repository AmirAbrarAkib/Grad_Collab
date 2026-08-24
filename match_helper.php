<?php
// ============================================================
// match_helper.php - Match Score Engine & Application Helpers
// ============================================================

/**
 * Ensures that the team_applications table exists in the database.
 */
function ensure_applications_table($pdo) {
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS team_applications (
                application_id INT PRIMARY KEY AUTO_INCREMENT,
                team_id        INT NOT NULL,
                student_id     INT NOT NULL,
                message        TEXT NOT NULL,
                match_score    INT NOT NULL DEFAULT 0,
                status         ENUM('pending','accepted','rejected') NOT NULL DEFAULT 'pending',
                applied_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (team_id) REFERENCES team_posts(team_id) ON DELETE CASCADE,
                FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE
            )
        ");
    } catch (PDOException $e) {
        // Table already exists or error handled
    }
}

/**
 * Splits a comma-separated string into a trimmed, lowercased array of items.
 */
function tokenize_list($string) {
    if (empty($string)) return [];
    $parts = explode(',', $string);
    $result = [];
    foreach ($parts as $part) {
        $trimmed = trim(mb_strtolower($part));
        if ($trimmed !== '') {
            $result[] = $trimmed;
        }
    }
    return array_unique($result);
}

/**
 * Calculates a match score (0 - 100) between a student profile and a research post.
 * 
 * Factors and Weights:
 * - Skills Alignment:       25 points
 * - Domain & Tags Match:    20 points
 * - CGPA Requirement:       20 points
 * - Semester Eligibility:   15 points
 * - Preferred Supervisor:   10 points
 * - Availability:           10 points
 * Total:                   100 points
 * 
 * @param array|null $profile  The student's profile array from student_profiles
 * @param array      $post     The research post array from team_posts
 * @return array               Structured score data with total, label, badge class, and breakdown
 */
function calculate_match_score($profile, $post) {
    // If student has no profile saved
    if (!$profile) {
        return [
            'score'       => 0,
            'badge_class' => 'match-low',
            'level'       => 'Profile Incomplete',
            'has_profile' => false,
            'breakdown'   => [
                'skills'       => ['name' => 'Skills Alignment', 'score' => 0, 'max' => 25, 'status' => 'Profile not set up'],
                'domain'       => ['name' => 'Domain & Tags', 'score' => 0, 'max' => 20, 'status' => 'Profile not set up'],
                'cgpa'         => ['name' => 'CGPA Eligibility', 'score' => 0, 'max' => 20, 'status' => 'Profile not set up'],
                'semester'     => ['name' => 'Semester Eligibility', 'score' => 0, 'max' => 15, 'status' => 'Profile not set up'],
                'supervisor'   => ['name' => 'Supervisor Match', 'score' => 0, 'max' => 10, 'status' => 'Profile not set up'],
                'availability' => ['name' => 'Availability', 'score' => 0, 'max' => 10, 'status' => 'Profile not set up']
            ]
        ];
    }

    // 1. Skills Match (25 Points)
    $req_skills_raw  = $post['required_skills'] ?? '';
    $user_skills_raw = $profile['skills'] ?? '';
    $req_skills      = tokenize_list($req_skills_raw);
    $user_skills     = tokenize_list($user_skills_raw);

    $skill_score  = 0;
    $skill_status = '';

    if (empty($req_skills)) {
        $skill_score  = 25;
        $skill_status = 'No specific skills required';
    } else {
        $matched_skills = [];
        foreach ($req_skills as $req) {
            foreach ($user_skills as $user_skill) {
                if ($req === $user_skill || strpos($user_skill, $req) !== false || strpos($req, $user_skill) !== false) {
                    $matched_skills[] = $req;
                    break;
                }
            }
        }
        $matched_count = count(array_unique($matched_skills));
        $total_req     = count($req_skills);
        $skill_score   = (int)round(25 * ($matched_count / $total_req));
        $skill_status  = $matched_count . '/' . $total_req . ' skills matched' . (!empty($matched_skills) ? ' (' . implode(', ', array_slice($matched_skills, 0, 3)) . ')' : '');
    }

    // 2. Domain & Tags Match (20 Points)
    $post_domain_raw = $post['domain'] ?? '';
    $post_tags_raw   = $post['tags'] ?? '';
    $user_domains_raw= $profile['preferred_domains'] ?? '';

    $user_domains = tokenize_list($user_domains_raw);
    $post_domain  = mb_strtolower(trim($post_domain_raw));
    $post_tags    = tokenize_list($post_tags_raw);

    $domain_pts = 0;
    $domain_status_parts = [];

    // Domain matching (up to 12 points)
    $domain_matched = false;
    if (!empty($post_domain)) {
        foreach ($user_domains as $ud) {
            if ($ud === $post_domain || strpos($ud, $post_domain) !== false || strpos($post_domain, $ud) !== false) {
                $domain_pts = 12;
                $domain_matched = true;
                $domain_status_parts[] = "Domain aligned ('" . htmlspecialchars($post_domain_raw) . "')";
                break;
            }
        }
        if (!$domain_matched) {
            if (!empty($user_domains)) {
                $domain_pts = 4; // partial for having domain preferences
                $domain_status_parts[] = "Different domain preference";
            } else {
                $domain_status_parts[] = "No preferred domains set";
            }
        }
    } else {
        $domain_pts = 12;
        $domain_status_parts[] = "General research area";
    }

    // Tags matching (up to 8 points)
    $tag_pts = 0;
    if (empty($post_tags)) {
        $tag_pts = 8;
    } else {
        $user_all_interests = array_merge($user_domains, $user_skills);
        $tag_matches = 0;
        foreach ($post_tags as $pt) {
            foreach ($user_all_interests as $uitem) {
                if ($pt === $uitem || strpos($uitem, $pt) !== false || strpos($pt, $uitem) !== false) {
                    $tag_matches++;
                    break;
                }
            }
        }
        $tag_pts = (int)round(8 * ($tag_matches / count($post_tags)));
        if ($tag_matches > 0) {
            $domain_status_parts[] = $tag_matches . '/' . count($post_tags) . ' tags matched';
        }
    }
    $domain_score  = min(20, $domain_pts + $tag_pts);
    $domain_status = implode('; ', $domain_status_parts);

    // 3. CGPA Requirement (20 Points)
    $min_cgpa  = (float)($post['minimum_cgpa'] ?? 0.0);
    $user_cgpa = (float)($profile['cgpa'] ?? 0.0);

    $cgpa_score  = 0;
    $cgpa_status = '';

    if ($min_cgpa <= 0.0) {
        $cgpa_score  = 20;
        $cgpa_status = 'No minimum CGPA requirement';
    } elseif ($user_cgpa >= $min_cgpa) {
        $cgpa_score  = 20;
        $cgpa_status = 'Meets requirement (' . number_format($user_cgpa, 2) . ' >= ' . number_format($min_cgpa, 2) . ')';
    } else {
        $cgpa_score  = 0;
        $cgpa_status = 'Below minimum (' . number_format($user_cgpa, 2) . ' < ' . number_format($min_cgpa, 2) . ')';
    }

    // 4. Semester Eligibility (15 Points)
    $elig_sem_raw = trim($post['eligible_semesters'] ?? '');
    $user_sem     = (int)($profile['semester'] ?? 0);

    $sem_score  = 0;
    $sem_status = '';

    if (empty($elig_sem_raw)) {
        $sem_score  = 15;
        $sem_status = 'Open to all semesters';
    } else {
        $sem_list = tokenize_list($elig_sem_raw);
        $user_sem_str = (string)$user_sem;
        $is_eligible = false;
        foreach ($sem_list as $s) {
            if ($s === $user_sem_str || $s === 'semester ' . $user_sem_str || $s === 'sem ' . $user_sem_str) {
                $is_eligible = true;
                break;
            }
        }
        if ($is_eligible) {
            $sem_score  = 15;
            $sem_status = 'Eligible (Semester ' . $user_sem . ')';
        } else {
            $sem_score  = 0;
            $sem_status = 'Not in eligible semesters (' . htmlspecialchars($elig_sem_raw) . ')';
        }
    }

    // 5. Preferred Supervisor Match (10 Points)
    $post_sup_raw = trim($post['preferred_supervisor'] ?? '');
    $user_sup_raw = trim($profile['preferred_supervisor'] ?? '');

    $sup_score  = 0;
    $sup_status = '';

    if (empty($post_sup_raw) || empty($user_sup_raw)) {
        $sup_score  = 5; // Neutral
        $sup_status = empty($post_sup_raw) ? 'No supervisor designated' : 'No supervisor preference set';
    } else {
        $post_sup_clean = mb_strtolower($post_sup_raw);
        $user_sup_clean = mb_strtolower($user_sup_raw);
        if ($post_sup_clean === $user_sup_clean || strpos($post_sup_clean, $user_sup_clean) !== false || strpos($user_sup_clean, $post_sup_clean) !== false) {
            $sup_score  = 10;
            $sup_status = 'Matched (' . htmlspecialchars($post_sup_raw) . ')';
        } else {
            $sup_score  = 0;
            $sup_status = 'Different preference';
        }
    }

    // 6. Availability (10 Points)
    $avail = $profile['availability'] ?? 'Available';
    $avail_score  = 0;
    $avail_status = '';

    if ($avail === 'Available') {
        $avail_score  = 10;
        $avail_status = 'Fully Available';
    } elseif ($avail === 'Partially Available') {
        $avail_score  = 6;
        $avail_status = 'Partially Available';
    } else {
        $avail_score  = 0;
        $avail_status = 'Not Available';
    }

    // Total Score calculation
    $total_score = $skill_score + $domain_score + $cgpa_score + $sem_score + $sup_score + $avail_score;
    $total_score = max(0, min(100, $total_score));

    if ($total_score >= 75) {
        $badge_class = 'match-high';
        $level       = 'Strong Match';
    } elseif ($total_score >= 50) {
        $badge_class = 'match-med';
        $level       = 'Moderate Match';
    } else {
        $badge_class = 'match-low';
        $level       = 'Low Match';
    }

    return [
        'score'       => $total_score,
        'badge_class' => $badge_class,
        'level'       => $level,
        'has_profile' => true,
        'breakdown'   => [
            'skills'       => ['name' => 'Skills Alignment', 'score' => $skill_score, 'max' => 25, 'status' => $skill_status],
            'domain'       => ['name' => 'Domain & Tags', 'score' => $domain_score, 'max' => 20, 'status' => $domain_status],
            'cgpa'         => ['name' => 'CGPA Eligibility', 'score' => $cgpa_score, 'max' => 20, 'status' => $cgpa_status],
            'semester'     => ['name' => 'Semester Eligibility', 'score' => $sem_score, 'max' => 15, 'status' => $sem_status],
            'supervisor'   => ['name' => 'Supervisor Match', 'score' => $sup_score, 'max' => 10, 'status' => $sup_status],
            'availability' => ['name' => 'Availability', 'score' => $avail_score, 'max' => 10, 'status' => $avail_status]
        ]
    ];
}

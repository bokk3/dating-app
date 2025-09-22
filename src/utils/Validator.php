<?php
class Validator {
    public static function validate($data, $rules) {
        $errors = [];
        
        foreach ($rules as $field => $ruleString) {
            $fieldRules = explode('|', $ruleString);
            $value = $data[$field] ?? null;
            
            foreach ($fieldRules as $rule) {
                $error = self::validateRule($field, $value, $rule, $data);
                if ($error) {
                    $errors[$field][] = $error;
                }
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    private static function validateRule($field, $value, $rule, $allData) {
        $ruleParts = explode(':', $rule);
        $ruleName = $ruleParts[0];
        $ruleValue = $ruleParts[1] ?? null;
        
        switch ($ruleName) {
            case 'required':
                if (empty($value) && $value !== '0') {
                    return ucfirst($field) . ' is required';
                }
                break;
                
            case 'email':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    return 'Invalid email format';
                }
                break;
                
            case 'min':
                if (!empty($value) && strlen($value) < (int)$ruleValue) {
                    return ucfirst($field) . " must be at least {$ruleValue} characters";
                }
                break;
                
            case 'max':
                if (!empty($value) && strlen($value) > (int)$ruleValue) {
                    return ucfirst($field) . " must not exceed {$ruleValue} characters";
                }
                break;
                
            case 'numeric':
                if (!empty($value) && !is_numeric($value)) {
                    return ucfirst($field) . ' must be a number';
                }
                break;
                
            case 'integer':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_INT)) {
                    return ucfirst($field) . ' must be an integer';
                }
                break;
                
            case 'in':
                $allowedValues = explode(',', $ruleValue);
                if (!empty($value) && !in_array($value, $allowedValues)) {
                    return ucfirst($field) . ' must be one of: ' . implode(', ', $allowedValues);
                }
                break;
                
            case 'date':
                if (!empty($value)) {
                    $date = DateTime::createFromFormat('Y-m-d', $value);
                    if (!$date || $date->format('Y-m-d') !== $value) {
                        return ucfirst($field) . ' must be a valid date (YYYY-MM-DD)';
                    }
                }
                break;
                
            case 'age_range':
                if (!empty($value)) {
                    $birthDate = DateTime::createFromFormat('Y-m-d', $value);
                    if ($birthDate) {
                        $age = (new DateTime())->diff($birthDate)->y;
                        if ($age < MIN_AGE || $age > MAX_AGE) {
                            return "Age must be between " . MIN_AGE . " and " . MAX_AGE;
                        }
                    }
                }
                break;
                
            case 'password_strength':
                if (!empty($value)) {
                    if (strlen($value) < 8) {
                        return 'Password must be at least 8 characters';
                    }
                    if (!preg_match('/[A-Z]/', $value)) {
                        return 'Password must contain at least one uppercase letter';
                    }
                    if (!preg_match('/[a-z]/', $value)) {
                        return 'Password must contain at least one lowercase letter';
                    }
                    if (!preg_match('/[0-9]/', $value)) {
                        return 'Password must contain at least one number';
                    }
                }
                break;
                
            case 'file_type':
                if (!empty($value) && isset($value['type'])) {
                    $allowedTypes = explode(',', $ruleValue);
                    if (!in_array($value['type'], $allowedTypes)) {
                        return 'Invalid file type. Allowed: ' . implode(', ', $allowedTypes);
                    }
                }
                break;
                
            case 'file_size':
                if (!empty($value) && isset($value['size'])) {
                    $maxSize = (int)$ruleValue;
                    if ($value['size'] > $maxSize) {
                        return 'File size too large. Maximum: ' . number_format($maxSize / (1024*1024), 1) . 'MB';
                    }
                }
                break;
        }
        
        return null;
    }
}
?>
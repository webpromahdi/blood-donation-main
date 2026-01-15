/**
 * Zod Form Validation Module
 * Shared schemas and utilities for form validation across the Blood Donation System
 */

import { z } from 'https://cdn.jsdelivr.net/npm/zod@3.22.4/+esm';

// ============================================
// SHARED SCHEMAS
// ============================================

export const emailSchema = z
  .string()
  .min(1, 'Email is required')
  .email('Please enter a valid email address');

export const optionalEmailSchema = z
  .string()
  .email('Please enter a valid email address')
  .optional()
  .or(z.literal(''));

export const passwordSchema = z
  .string()
  .min(1, 'Password is required')
  .min(6, 'Password must be at least 6 characters')
  .regex(/[A-Z]/, 'Password must include at least one uppercase letter')
  .regex(/[a-z]/, 'Password must include at least one lowercase letter')
  .regex(/[0-9]/, 'Password must include at least one number')
  .regex(/[^A-Za-z0-9]/, 'Password must include at least one special character');

export const loginPasswordSchema = z
  .string()
  .min(1, 'Password is required');

export const phoneSchema = z
  .string()
  .min(1, 'Phone number is required')
  .min(7, 'Please enter a valid phone number');

export const bloodTypeSchema = z
  .string()
  .min(1, 'Blood type is required')
  .refine(val => ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'].includes(val), {
    message: 'Please select a valid blood type'
  });

export const requiredString = (fieldName) => z
  .string()
  .min(1, `${fieldName} is required`);

export const requiredNumber = (fieldName, min = 1) => z
  .string()
  .min(1, `${fieldName} is required`)
  .refine(val => !isNaN(Number(val)) && Number(val) >= min, {
    message: `${fieldName} must be at least ${min}`
  });

// ============================================
// UTILITY FUNCTIONS
// ============================================

/**
 * Show error message below a form field
 */
export function showFieldError(fieldId, message) {
  const field = document.getElementById(fieldId);
  const errorEl = document.getElementById(`${fieldId}Error`);
  
  if (field) {
    field.classList.add('border-red-500');
    field.classList.remove('border-gray-300');
  }
  
  if (errorEl) {
    errorEl.textContent = message;
    errorEl.classList.remove('hidden');
  }
}

/**
 * Clear error message for a form field
 */
export function clearFieldError(fieldId) {
  const field = document.getElementById(fieldId);
  const errorEl = document.getElementById(`${fieldId}Error`);
  
  if (field) {
    field.classList.remove('border-red-500');
    field.classList.add('border-gray-300');
  }
  
  if (errorEl) {
    errorEl.textContent = '';
    errorEl.classList.add('hidden');
  }
}

/**
 * Clear all errors for given field IDs
 */
export function clearAllErrors(fieldIds) {
  fieldIds.forEach(id => clearFieldError(id));
}

/**
 * Validate form data against a Zod schema
 * @returns {object} { success: boolean, data?: object, errors?: object }
 */
export function validateForm(schema, formData) {
  const result = schema.safeParse(formData);
  
  if (result.success) {
    return { success: true, data: result.data };
  }
  
  // Convert Zod errors to field-based error object
  const errors = {};
  result.error.errors.forEach(err => {
    const field = err.path[0];
    if (field && !errors[field]) {
      errors[field] = err.message;
    }
  });
  
  return { success: false, errors };
}

/**
 * Display all validation errors on the form
 */
export function displayErrors(errors) {
  Object.entries(errors).forEach(([fieldId, message]) => {
    showFieldError(fieldId, message);
  });
}

/**
 * Setup live validation - clear errors when user types
 */
export function setupLiveValidation(formId, fieldIds) {
  const form = document.getElementById(formId);
  if (!form) return;
  
  fieldIds.forEach(fieldId => {
    const field = document.getElementById(fieldId);
    if (field) {
      field.addEventListener('input', () => clearFieldError(fieldId));
      field.addEventListener('change', () => clearFieldError(fieldId));
    }
  });
}

/**
 * Get form data as an object from form element
 */
export function getFormData(formId) {
  const form = document.getElementById(formId);
  if (!form) return {};
  
  const formData = new FormData(form);
  const data = {};
  for (const [key, value] of formData.entries()) {
    data[key] = value;
  }
  return data;
}

// Export Zod for use in form-specific schemas
export { z };

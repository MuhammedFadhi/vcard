const sgMail = require('@sendgrid/mail');
require('dotenv').config();

sgMail.setApiKey(process.env.SENDGRID_API_KEY);

const sendEmail = async (to, subject, html) => {
    const msg = {
        to,
        from: process.env.EMAIL_FROM, // Use your verified SendGrid sender
        subject,
        html,
    };

    try {
        await sgMail.send(msg);
        console.log(`📧 Email sent to: ${to}`);
        return { success: true };
    } catch (error) {
        console.error('❌ SendGrid Error:', error.response ? error.response.body : error.message);
        return { success: false, error: error.message };
    }
};

const sendOTPEmail = async (email, otp, companyName) => {
    const html = `
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 10px;">
            <h2 style="color: #6366f1; text-align: center;">${companyName} Login Code</h2>
            <p>Hello,</p>
            <p>Use the following 6-digit code to log in to your digital business card management portal:</p>
            <div style="background: #f8fafc; padding: 20px; text-align: center; font-size: 32px; font-weight: bold; letter-spacing: 5px; color: #1e293b; border-radius: 8px; margin: 20px 0;">
                ${otp}
            </div>
            <p style="color: #64748b; font-size: 14px; text-align: center;">This code will expire in 10 minutes. If you did not request this, please ignore this email.</p>
        </div>
    `;
    return sendEmail(email, `Your Login Code for ${companyName}`, html);
};

const sendCompanyOTPEmail = async (email, otp, companyName) => {
    const html = `
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 10px;">
            <h2 style="color: #4f46e5; text-align: center;">Admin Login Verification</h2>
            <p>Hello, <strong>${companyName} Admin</strong>,</p>
            <p>A login attempt was made to your company dashboard. Please use the code below to complete your sign-in:</p>
            <div style="background: #f1f5f9; padding: 24px; text-align: center; font-size: 36px; font-weight: bold; letter-spacing: 8px; color: #1e293b; border-radius: 8px; margin: 20px 0;">
                ${otp}
            </div>
            <p style="color: #64748b; font-size: 14px; text-align: center;">This code expires in <strong>10 minutes</strong>. If you did not attempt to log in, please secure your account immediately.</p>
        </div>
    `;
    return sendEmail(email, `Your Admin Login Code — ${companyName}`, html);
};

const sendWelcomeInvite = async (email, companyName) => {
    const frontendUrl = process.env.FRONTEND_URL || 'https://vcard.effedo.com';
    const loginLink = `${frontendUrl}/?view=employee`;
    const html = `
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 10px;">
            <h2 style="color: #6366f1; text-align: center;">Welcome to ${companyName}</h2>
            <p>Hello,</p>
            <p>Your digital business card is ready! You can now log in to manage your profile and details.</p>
            <div style="text-align: center; margin: 30px 0;">
                <a href="${loginLink}" style="background: #6366f1; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;">Login to My Dashboard</a>
            </div>
            <p style="color: #64748b; font-size: 14px; text-align: center;">Simply use your email address to receive a login code.</p>
        </div>
    `;
    return sendEmail(email, `Welcome to ${companyName} - Manage your Digital Card`, html);
};

module.exports = { sendOTPEmail, sendWelcomeInvite, sendCompanyOTPEmail };

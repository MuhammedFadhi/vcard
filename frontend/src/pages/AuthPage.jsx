import React, { useState } from 'react';
import axios from 'axios';
import { useNavigate, useSearchParams } from 'react-router-dom';

const AuthPage = () => {
    const [searchParams] = useSearchParams();
    const [view, setView] = useState(searchParams.get('view') || 'login'); // 'login', 'register', 'employee'
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [otp, setOtp] = useState('');
    const [otpSent, setOtpSent] = useState(false);
    const [companyName, setCompanyName] = useState('');
    const [createdBy, setCreatedBy] = useState('');
    const [logo, setLogo] = useState(null);
    const [error, setError] = useState('');
    const [loading, setLoading] = useState(false);
    
    const navigate = useNavigate();
    axios.defaults.withCredentials = true;

    const handleAdminLogin = async (e) => {
        e.preventDefault();
        setLoading(true);
        setError('');
        try {
            const res = await axios.post('/api/auth/login', { email, password });
            if (res.data.success) navigate(`/${res.data.company_slug}`);
        } catch (err) {
            setError(err.response?.data?.message || 'Login failed');
        } finally { setLoading(false); }
    };

    const handleRequestOTP = async (e) => {
        e.preventDefault();
        setLoading(true);
        setError('');
        try {
            await axios.post('/api/auth/employee/request-otp', { email });
            setOtpSent(true);
        } catch (err) {
            setError(err.response?.data?.message || 'Employee not found');
        } finally { setLoading(false); }
    };

    const handleVerifyOTP = async (e) => {
        e.preventDefault();
        setLoading(true);
        setError('');
        try {
            const res = await axios.post('/api/auth/employee/verify-otp', { email, otp });
            if (res.data.success) navigate(`/${res.data.company_slug}/manage`); // Private manage route
        } catch (err) {
            setError(err.response?.data?.message || 'Invalid code');
        } finally { setLoading(false); }
    };

    const handleRegister = async (e) => {
        e.preventDefault();
        setLoading(true);
        setError('');
        const formData = new FormData();
        formData.append('company_name', companyName);
        formData.append('created_by', createdBy);
        formData.append('email', email);
        formData.append('password', password);
        if (logo) formData.append('logo', logo);

        try {
            const res = await axios.post('/api/auth/register', formData);
            if (res.data.success) navigate(`/${res.data.company_slug}`);
        } catch (err) {
            setError(err.response?.data?.message || 'Registration failed');
        } finally { setLoading(false); }
    };

    return (
        <div className="auth-container">
            <div className="form-box">
                <div className="form-header">
                    <img src="https://effedo.app/vcard/effedo_logo.png" alt="Logo" />
                    <h1>{view === 'employee' ? 'Employee Access' : view === 'register' ? 'Register Company' : 'Company Login'}</h1>
                    <p className="text-muted small">
                        {view === 'employee' ? 'Access your private vCard dashboard' : 'Manage your digital business cards'}
                    </p>
                </div>

                {error && <div className="error-message mb-3">{error}</div>}

                {/* --- COMPANY LOGIN --- */}
                {view === 'login' && (
                    <form onSubmit={handleAdminLogin}>
                        <div className="form-group">
                            <label>Admin Email</label>
                            <input type="email" value={email} onChange={(e) => setEmail(e.target.value)} placeholder="name@company.com" required />
                        </div>
                        <div className="form-group">
                            <label>Password</label>
                            <input type="password" value={password} onChange={(e) => setPassword(e.target.value)} placeholder="••••••••" required />
                        </div>
                        <button type="submit" disabled={loading}>{loading ? 'Authenticating...' : 'Log In as Admin'}</button>
                        <div className="form-footer d-flex flex-column gap-2 mt-4">
                            <a href="#" onClick={() => setView('employee')} className="fw-bold text-primary">Are you an Employee? Login here</a>
                            <a href="#" onClick={() => setView('register')}>Don't have a company account? Sign Up</a>
                        </div>
                    </form>
                )}

                {/* --- EMPLOYEE OTP LOGIN --- */}
                {view === 'employee' && (
                    <form onSubmit={otpSent ? handleVerifyOTP : handleRequestOTP}>
                        <div className="form-group">
                            <label>Your Work Email</label>
                            <input type="email" value={email} onChange={(e) => setEmail(e.target.value)} placeholder="name@company.com" disabled={otpSent} required />
                        </div>
                        
                        {otpSent && (
                            <div className="form-group animate__animated animate__fadeIn">
                                <label>Enter 6-Digit Code</label>
                                <input type="text" value={otp} onChange={(e) => setOtp(e.target.value)} placeholder="000000" maxLength="6" className="text-center fw-bold fs-4" style={{letterSpacing:'8px'}} required />
                                <small className="text-muted d-block text-center mt-2">We've sent a code to your email.</small>
                            </div>
                        )}

                        <button type="submit" disabled={loading} className={otpSent ? 'btn-success' : ''}>
                            {loading ? 'Processing...' : otpSent ? 'Verify & Access' : 'Send Login Code'}
                        </button>
                        
                        <div className="form-footer mt-4">
                            <a href="#" onClick={() => { setView('login'); setOtpSent(false); }}>Back to Admin Login</a>
                        </div>
                    </form>
                )}

                {/* --- REGISTER --- */}
                {view === 'register' && (
                    <form onSubmit={handleRegister}>
                        <div className="form-group">
                            <label>Company Name</label>
                            <input type="text" value={companyName} onChange={(e) => setCompanyName(e.target.value)} placeholder="e.g. Acme Corp" required />
                        </div>
                        <div className="form-group">
                            <label>Company Logo</label>
                            <input type="file" onChange={(e) => setLogo(e.target.files[0])} accept="image/*" />
                        </div>
                        <div className="form-group">
                            <label>Admin Name</label>
                            <input type="text" value={createdBy} onChange={(e) => setCreatedBy(e.target.value)} placeholder="Your full name" required />
                        </div>
                        <div className="form-group">
                            <label>Work Email</label>
                            <input type="email" value={email} onChange={(e) => setEmail(e.target.value)} placeholder="name@company.com" required />
                        </div>
                        <div className="form-group">
                            <label>Create Password</label>
                            <input type="password" value={password} onChange={(e) => setPassword(e.target.value)} placeholder="••••••••" required />
                        </div>
                        <button type="submit" disabled={loading}>{loading ? 'Creating Account...' : 'Create Company Account'}</button>
                        <div className="form-footer mt-4">
                            <a href="#" onClick={() => setView('login')}>Already have an account? Login</a>
                        </div>
                    </form>
                )}
            </div>
        </div>
    );
};

export default AuthPage;

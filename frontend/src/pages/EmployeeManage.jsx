import React, { useState, useEffect } from 'react';
import axios from 'axios';
import { useParams, useNavigate } from 'react-router-dom';

const EmployeeManage = () => {
    const { companySlug } = useParams();
    const navigate = useNavigate();
    const [employee, setEmployee] = useState(null);
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [photo, setPhoto] = useState(null);
    const [message, setMessage] = useState({ text: '', type: '' });

    const [formData, setFormData] = useState({
        full_name: '',
        designation: '',
        phone: '',
        website: '',
        linkedin: '',
        instagram: '',
        facebook: '',
        twitter: ''
    });

    useEffect(() => {
        fetchProfile();
    }, []);

    const fetchProfile = async () => {
        try {
            const res = await axios.get('http://localhost:5000/api/employee/me');
            if (res.data.success) {
                const emp = res.data.employee;
                setEmployee(emp);
                setFormData({
                    full_name: emp.full_name || '',
                    designation: emp.designation || '',
                    phone: emp.phone || '',
                    website: emp.website || '',
                    linkedin: emp.linkedin || '',
                    instagram: emp.instagram || '',
                    facebook: emp.facebook || '',
                    twitter: emp.twitter || ''
                });
            }
        } catch (err) {
            navigate('/');
        } finally {
            setLoading(false);
        }
    };

    const handleSave = async (e) => {
        e.preventDefault();
        setSaving(true);
        const data = new FormData();
        Object.keys(formData).forEach(key => data.append(key, formData[key]));
        if (photo) data.append('photo', photo);

        try {
            await axios.put('http://localhost:5000/api/employee/update-me', data);
            setMessage({ text: 'Profile updated successfully!', type: 'success' });
            setTimeout(() => setMessage({ text: '', type: '' }), 3000);
        } catch (err) {
            setMessage({ text: 'Failed to update profile.', type: 'danger' });
        } finally {
            setSaving(false);
        }
    };

    if (loading) return <div className="d-flex justify-content-center align-items-center vh-100"><div className="spinner-border text-primary"></div></div>;

    const theme = employee?.companies || {};
    const gradient = `linear-gradient(135deg, ${theme.theme_color1 || '#667eea'} 0%, ${theme.theme_color2 || '#764ba2'} 100%)`;

    return (
        <div className="dashboard-wrapper" style={{ background: gradient, minHeight: '100vh', padding: '40px 20px' }}>
            <div className="container" style={{ maxWidth: '800px' }}>
                <div className="glass-card animate__animated animate__fadeIn">
                    <div className="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h2 className="fw-bold mb-0">Manage Your Digital Card</h2>
                            <p className="text-muted">Update your public profile details</p>
                        </div>
                        <button onClick={() => navigate(`/${companySlug}/${employee?.emp_code}`)} className="btn btn-outline-primary btn-sm rounded-pill">
                            <i className="bi bi-eye me-2"></i>View My Card
                        </button>
                    </div>

                    {message.text && <div className={`alert alert-${message.type} rounded-4`}>{message.text}</div>}

                    <form onSubmit={handleSave}>
                        <div className="row g-4">
                            {/* Profile Image Section */}
                            <div className="col-12 text-center mb-3">
                                <div className="position-relative d-inline-block">
                                    <img 
                                        src={photo ? URL.createObjectURL(photo) : (employee?.photo ? `http://localhost:5000/${employee.photo}` : `https://ui-avatars.com/api/?name=${encodeURIComponent(employee?.full_name || 'User')}&background=${(theme.theme_color1 || '#667eea').replace('#','')}&color=ffffff&size=200`)} 
                                        alt="Profile" 
                                        className="rounded-circle shadow-lg"
                                        style={{ width: '150px', height: '150px', objectFit: 'cover', border: '5px solid white' }}
                                    />
                                    <label className="btn btn-dark btn-sm position-absolute bottom-0 end-0 rounded-circle" style={{ width: '40px', height: '40px', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                                        <i className="bi bi-camera-fill"></i>
                                        <input type="file" hidden onChange={(e) => setPhoto(e.target.files[0])} accept="image/*" />
                                    </label>
                                </div>
                            </div>

                            {/* Info Fields */}
                            <div className="col-md-6">
                                <label className="form-label fw-bold">Full Name</label>
                                <input type="text" className="form-control rounded-3" value={formData.full_name} onChange={(e) => setFormData({...formData, full_name: e.target.value})} required />
                            </div>
                            <div className="col-md-6">
                                <label className="form-label fw-bold">Designation</label>
                                <input type="text" className="form-control rounded-3" value={formData.designation} onChange={(e) => setFormData({...formData, designation: e.target.value})} />
                            </div>
                            <div className="col-md-12">
                                <label className="form-label fw-bold">Phone Number</label>
                                <input type="text" className="form-control rounded-3" value={formData.phone} onChange={(e) => setFormData({...formData, phone: e.target.value})} />
                            </div>

                            <hr className="my-4" />
                            <h5 className="fw-bold mb-0">Social & Professional Links</h5>

                            <div className="col-md-6">
                                <label className="form-label small text-muted">Website URL</label>
                                <input type="url" className="form-control rounded-3" value={formData.website} onChange={(e) => setFormData({...formData, website: e.target.value})} />
                            </div>
                            <div className="col-md-6">
                                <label className="form-label small text-muted">LinkedIn Profile</label>
                                <input type="url" className="form-control rounded-3" value={formData.linkedin} onChange={(e) => setFormData({...formData, linkedin: e.target.value})} />
                            </div>
                            <div className="col-md-4">
                                <label className="form-label small text-muted">Instagram</label>
                                <input type="url" className="form-control rounded-3" value={formData.instagram} onChange={(e) => setFormData({...formData, instagram: e.target.value})} />
                            </div>
                            <div className="col-md-4">
                                <label className="form-label small text-muted">Facebook</label>
                                <input type="url" className="form-control rounded-3" value={formData.facebook} onChange={(e) => setFormData({...formData, facebook: e.target.value})} />
                            </div>
                            <div className="col-md-4">
                                <label className="form-label small text-muted">Twitter / X</label>
                                <input type="url" className="form-control rounded-3" value={formData.twitter} onChange={(e) => setFormData({...formData, twitter: e.target.value})} />
                            </div>

                            <div className="col-12 mt-5">
                                <button type="submit" disabled={saving} className="btn btn-primary w-100 py-3 rounded-4 fw-bold shadow-sm">
                                    {saving ? 'Saving Changes...' : 'Update My Digital Card'}
                                </button>
                                <button type="button" onClick={() => { axios.post('http://localhost:5000/api/auth/logout').then(() => navigate('/')); }} className="btn btn-link w-100 mt-3 text-danger text-decoration-none small">
                                    <i className="bi bi-box-arrow-right me-2"></i>Logout from Session
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    );
};

export default EmployeeManage;

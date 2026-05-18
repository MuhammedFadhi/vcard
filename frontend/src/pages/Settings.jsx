import React, { useState, useEffect } from 'react';
import axios from 'axios';
import { useNavigate, useParams, Link } from 'react-router-dom';

const Settings = () => {
    const { companySlug } = useParams();
    const [company, setCompany] = useState(null);
    const [loading, setLoading] = useState(true);
    const [logo, setLogo] = useState(null);
    const [formData, setFormData] = useState({
        company_name: '',
        admin_name: '',
        email: '',
        theme_color1: '#667eea',
        theme_color2: '#764ba2',
        password: '',
        website: '',
        linkedin: '',
        instagram: '',
        facebook: '',
        twitter: ''
    });

    const navigate = useNavigate();
    axios.defaults.withCredentials = true;

    const presets = [
        { c1: '#667eea', c2: '#764ba2' },
        { c1: '#ff7eb3', c2: '#ff758c' },
        { c1: '#2af5ff', c2: '#0099ff' },
        { c1: '#4facfe', c2: '#00f2fe' },
        { c1: '#f093fb', c2: '#f5576c' },
        { c1: '#f6d365', c2: '#fda085' },
        { c1: '#43e97b', c2: '#38f9d7' },
        { c1: '#5ee7df', c2: '#b490ca' }
    ];

    useEffect(() => {
        fetchCompany();
    }, []);

    const fetchCompany = async () => {
        try {
            const res = await axios.get('http://localhost:5000/api/auth/me');
            if (!res.data.loggedIn) {
                navigate('/');
                return;
            }
            const data = res.data.company;
            const socials = data.social_links || {};
            setCompany(data);
            setFormData({
                company_name: data.company_name || '',
                admin_name: data.created_by || 'Admin',
                email: data.email || '',
                theme_color1: data.theme_color1 || '#667eea',
                theme_color2: data.theme_color2 || '#764ba2',
                password: '',
                website: socials.website || '',
                linkedin: socials.linkedin || '',
                instagram: socials.instagram || '',
                facebook: socials.facebook || '',
                twitter: socials.twitter || ''
            });
            setLoading(false);
        } catch (err) {
            navigate('/');
        }
    };

    const handleInputChange = (e) => {
        setFormData({ ...formData, [e.target.name]: e.target.value });
    };

    const handlePresetClick = (c1, c2) => {
        setFormData({ ...formData, theme_color1: c1, theme_color2: c2 });
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        const data = new FormData();
        Object.keys(formData).forEach(key => data.append(key, formData[key]));
        if (logo) data.append('logo', logo);

        try {
            const res = await axios.put('http://localhost:5000/api/company/update', data);
            if (res.data.success) {
                alert('Settings updated successfully!');
                window.location.reload();
            } else {
                alert('Error: ' + res.data.message);
            }
        } catch (err) {
            const msg = err.response?.data?.message || err.message;
            alert('Error updating settings: ' + msg);
        }
    };

    if (loading) return <div className="text-white text-center mt-5">Loading Settings...</div>;

    return (
        <div className="dashboard-wrapper pb-5" style={{ background: `linear-gradient(135deg, ${formData.theme_color1} 0%, ${formData.theme_color2} 100%)` }}>
            {/* Header Exact Match */}
            <nav className="navbar navbar-premium mb-0">
                <div className="container-fluid px-4 px-md-5">
                    <div className="brand-wrap-premium d-flex align-items-center gap-2">
                        {company?.logo && <img src={`http://localhost:5000/${company.logo}`} alt="logo" style={{height:'40px', width:'40px', borderRadius:'10px'}} />}
                        <span className="fw-bold text-dark fs-5">{company?.company_name?.toUpperCase()} SETTINGS</span>
                    </div>
                    <div className="d-flex gap-2">
                        <Link to={`/dashboard/${companySlug}`} className="btn btn-light btn-sm px-3 rounded-3 border d-flex align-items-center gap-2">
                            <i className="bi bi-grid"></i> Dashboard
                        </Link>
                        <button className="btn btn-outline-danger btn-sm px-3 rounded-3" onClick={() => navigate('/logout')}>
                            <i className="bi bi-box-arrow-right"></i> Logout
                        </button>
                    </div>
                </div>
            </nav>

            <div className="container py-4" style={{ maxWidth: '900px' }}>
                <form onSubmit={handleSubmit} className="bg-white shadow-xl p-4 p-md-5 mb-5 position-relative z-2" style={{ borderRadius: '30px' }}>
                    <div className="d-flex align-items-center gap-2 mb-4">
                        <div style={{width:'4px', height:'24px', background:'#6366f1', borderRadius:'2px'}}></div>
                        <h3 className="fw-bold m-0">Company Profile</h3>
                    </div>

                    {/* Theme Section */}
                    <div className="p-4 rounded-4 mb-4" style={{ background: '#f8fafc', border: '1px solid #e2e8f0' }}>
                        <div className="d-flex align-items-center gap-2 mb-2">
                            <i className="bi bi-palette text-dark"></i>
                            <span className="fw-bold">Brand Theme Colors</span>
                        </div>
                        <small className="text-muted d-block mb-3">Choose your brand's gradient colors. This will apply across all pages.</small>
                        
                        <div className="row g-3 mb-4">
                            <div className="col-md-6">
                                <label className="small fw-bold mb-1 d-flex align-items-center gap-1">
                                    <span style={{width:'10px', height:'10px', background:'#6366f1', borderRadius:'50%'}}></span> Primary Color
                                </label>
                                <div className="d-flex align-items-center bg-white border rounded-3 p-2">
                                    <input type="color" name="theme_color1" value={formData.theme_color1} onChange={handleInputChange} className="border-0 p-0 bg-transparent" style={{width:'40px', height:'40px', cursor:'pointer'}} />
                                    <input type="text" value={formData.theme_color1.toUpperCase()} readOnly className="border-0 bg-transparent ms-2 fw-bold" style={{outline:'none'}} />
                                </div>
                            </div>
                            <div className="col-md-6">
                                <label className="small fw-bold mb-1 d-flex align-items-center gap-1">
                                    <span style={{width:'10px', height:'10px', background:'#8b5cf6', borderRadius:'50%'}}></span> Secondary Color
                                </label>
                                <div className="d-flex align-items-center bg-white border rounded-3 p-2">
                                    <input type="color" name="theme_color2" value={formData.theme_color2} onChange={handleInputChange} className="border-0 p-0 bg-transparent" style={{width:'40px', height:'40px', cursor:'pointer'}} />
                                    <input type="text" value={formData.theme_color2.toUpperCase()} readOnly className="border-0 bg-transparent ms-2 fw-bold" style={{outline:'none'}} />
                                </div>
                            </div>
                        </div>

                        <label className="small fw-bold mb-2 d-flex align-items-center gap-2">
                            <i className="bi bi-eye"></i> Live Preview
                        </label>
                        <div className="w-100 rounded-3 shadow-sm mb-4" style={{ height: '60px', background: `linear-gradient(135deg, ${formData.theme_color1} 0%, ${formData.theme_color2} 100%)` }}></div>

                        <label className="small fw-bold mb-2 d-flex align-items-center gap-2">
                            <i className="bi bi-star-fill text-warning"></i> Preset Themes
                        </label>
                        <div className="d-flex flex-wrap gap-2">
                            {presets.map((p, idx) => (
                                <div key={idx} onClick={() => handlePresetClick(p.c1, p.c2)} 
                                     style={{ width:'80px', height:'45px', borderRadius:'10px', cursor:'pointer', background:`linear-gradient(135deg, ${p.c1} 0%, ${p.c2} 100%)`, border: formData.theme_color1 === p.c1 ? '2px solid #000' : 'none' }}>
                                </div>
                            ))}
                        </div>
                    </div>

                    {/* Info Section */}
                    <div className="row g-4 mb-4">
                        <div className="col-md-6">
                            <label className="small fw-bold mb-2 d-flex align-items-center gap-2"><i className="bi bi-building"></i> Company Name</label>
                            <input type="text" name="company_name" value={formData.company_name} onChange={handleInputChange} className="form-control py-3 px-4 rounded-3 bg-light border-0" />
                        </div>
                        <div className="col-md-6">
                            <label className="small fw-bold mb-2 d-flex align-items-center gap-2"><i className="bi bi-person-badge"></i> Created By (Admin Name)</label>
                            <input type="text" name="admin_name" value={formData.admin_name} onChange={handleInputChange} className="form-control py-3 px-4 rounded-3 bg-light border-0" />
                        </div>
                        <div className="col-md-6">
                            <label className="small fw-bold mb-2 d-flex align-items-center gap-2"><i className="bi bi-envelope"></i> Email</label>
                            <input type="email" name="email" value={formData.email} onChange={handleInputChange} className="form-control py-3 px-4 rounded-3 bg-light border-0" />
                        </div>
                        <div className="col-md-6">
                            <label className="small fw-bold mb-2 d-flex align-items-center gap-2"><i className="bi bi-image"></i> Replace Logo (optional)</label>
                            <input type="file" onChange={(e) => setLogo(e.target.files[0])} className="form-control py-3 px-4 rounded-3 bg-light border-0" />
                            {company?.logo && <small className="text-success mt-1 d-block"><i className="bi bi-check-circle"></i> Current logo: <Link to={`http://localhost:5000/${company.logo}`} target="_blank" className="text-decoration-none">view logo</Link></small>}
                        </div>
                        <div className="col-12">
                            <label className="small fw-bold mb-2 d-flex align-items-center gap-2"><i className="bi bi-shield-lock"></i> New Password (optional)</label>
                            <input type="password" name="password" value={formData.password} onChange={handleInputChange} className="form-control py-3 px-4 rounded-3 bg-light border-0" placeholder="Leave blank to keep current password" />
                            <small className="text-muted mt-1 d-block"><i className="bi bi-info-circle"></i> Only enter a password if you want to change it</small>
                        </div>
                    </div>

                    {/* Social Media Links */}
                    <div className="mb-4">
                        <div className="d-flex align-items-center gap-2 mb-1">
                            <i className="bi bi-globe2 text-dark"></i>
                            <span className="fw-bold">Social Media Links</span>
                        </div>
                        <small className="text-muted d-block mb-3">Add your official company social media links. They will also show in your employee cards.</small>
                        
                        <div className="row g-3">
                            <div className="col-md-12">
                                <label className="small fw-bold mb-1"><i className="bi bi-browser-chrome"></i> Website</label>
                                <input type="text" name="website" value={formData.website} onChange={handleInputChange} className="form-control py-3 px-4 rounded-3 border" placeholder="https://www.yourcompany.com" />
                            </div>
                            <div className="col-md-6">
                                <label className="small fw-bold mb-1"><i className="bi bi-linkedin"></i> LinkedIn</label>
                                <input type="text" name="linkedin" value={formData.linkedin} onChange={handleInputChange} className="form-control py-3 px-4 rounded-3 border" />
                            </div>
                            <div className="col-md-6">
                                <label className="small fw-bold mb-1"><i className="bi bi-instagram"></i> Instagram</label>
                                <input type="text" name="instagram" value={formData.instagram} onChange={handleInputChange} className="form-control py-3 px-4 rounded-3 border" />
                            </div>
                            <div className="col-md-6">
                                <label className="small fw-bold mb-1"><i className="bi bi-facebook"></i> Facebook</label>
                                <input type="text" name="facebook" value={formData.facebook} onChange={handleInputChange} className="form-control py-3 px-4 rounded-3 border" />
                            </div>
                            <div className="col-md-6">
                                <label className="small fw-bold mb-1"><i className="bi bi-twitter-x"></i> Twitter / X</label>
                                <input type="text" name="twitter" value={formData.twitter} onChange={handleInputChange} className="form-control py-3 px-4 rounded-3 border" />
                            </div>
                        </div>
                    </div>

                    <div className="bg-light p-2 rounded-2 d-inline-flex align-items-center gap-2 mb-5">
                        <i className="bi bi-link-45deg text-primary"></i>
                        <span className="small text-primary fw-bold">Company Slug: {companySlug}</span>
                    </div>

                    <div className="d-flex justify-content-end pt-4 border-top">
                        <button type="submit" className="btn btn-primary px-5 py-3 rounded-4 fw-bold shadow-lg d-flex align-items-center gap-2" style={{ background: '#6366f1' }}>
                            <i className="bi bi-cloud-arrow-up"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
};

export default Settings;

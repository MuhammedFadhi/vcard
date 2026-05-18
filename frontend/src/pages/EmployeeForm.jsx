import React, { useState, useEffect, useCallback } from 'react';
import axios from 'axios';
import { useNavigate, useParams } from 'react-router-dom';
import Cropper from 'react-easy-crop';
import { getCroppedImg } from '../utils/cropImage';

const EmployeeForm = () => {
    const [formData, setFormData] = useState({
        emp_name: '',
        designation: '',
        phone: '',
        email: '',
        whatsapp: '',
        headline: '',
        about: '',
        linkedin: '',
        instagram: '',
        facebook: '',
        twitter: '',
        website: '',
        maps: '',
        brochure: ''
    });
    const [photo, setPhoto] = useState(null);
    const [photoPreview, setPhotoPreview] = useState(null);
    const [loading, setLoading] = useState(false);
    const [company, setCompany] = useState(null);
    const navigate = useNavigate();
    const { id } = useParams();

    // Cropping State
    const [crop, setCrop] = useState({ x: 0, y: 0 });
    const [zoom, setZoom] = useState(1);
    const [croppedAreaPixels, setCroppedAreaPixels] = useState(null);
    const [showCropper, setShowCropper] = useState(false);
    const [imageToCrop, setImageToCrop] = useState(null);
    const [originalImage, setOriginalImage] = useState(null);

    axios.defaults.withCredentials = true;

    useEffect(() => {
        fetchCompany();
        if (id) {
            fetchEmployee();
        }
    }, [id]);

    const fetchCompany = async () => {
        try {
            const res = await axios.get('http://localhost:5000/api/auth/me');
            if (res.data.loggedIn) setCompany(res.data.company);
        } catch (err) {
            console.error('Failed to fetch company');
        }
    };

    const fetchEmployee = async () => {
        try {
            const res = await axios.get(`http://localhost:5000/api/employees/${id}`);
            const data = res.data;
            setFormData({
                emp_name: data.emp_name || '',
                designation: data.designation || '',
                phone: data.phone || '',
                email: data.email || '',
                whatsapp: data.whatsapp || '',
                headline: data.headline || '',
                about: data.about || '',
                linkedin: data.linkedin || '',
                instagram: data.instagram || '',
                facebook: data.facebook || '',
                twitter: data.twitter || '',
                website: data.website || '',
                maps: data.maps || '',
                brochure: data.brochure || ''
            });
            if (data.photo) setPhotoPreview(`http://localhost:5000/${data.photo}`);
        } catch (err) {
            console.error('Failed to fetch employee');
        }
    };

    const handleChange = (e) => {
        setFormData({ ...formData, [e.target.name]: e.target.value });
    };

    const handleFileChange = (e) => {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onloadend = () => {
                setOriginalImage(reader.result);
                setImageToCrop(reader.result);
                setShowCropper(true);
            };
            reader.readAsDataURL(file);
        }
    };

    const onCropComplete = useCallback((croppedArea, croppedAreaPixels) => {
        setCroppedAreaPixels(croppedAreaPixels);
    }, []);

    const saveCroppedImage = async () => {
        try {
            const croppedBlob = await getCroppedImg(imageToCrop, croppedAreaPixels);
            const croppedFile = new File([croppedBlob], 'photo.jpg', { type: 'image/jpeg' });
            setPhoto(croppedFile);
            setPhotoPreview(URL.createObjectURL(croppedBlob));
            setShowCropper(false);
        } catch (e) {
            console.error(e);
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setLoading(true);
        const data = new FormData();
        Object.keys(formData).forEach(key => data.append(key, formData[key]));
        if (photo) data.append('photo', photo);

        try {
            if (id) {
                await axios.put(`http://localhost:5000/api/employees/${id}`, data);
            } else {
                await axios.post('http://localhost:5000/api/employees', data);
            }
            navigate(`/${company.company_slug}`);
        } catch (err) {
            alert('Failed to save employee');
        } finally {
            setLoading(false);
        }
    };

    const theme1 = company?.theme_color1 || '#8b5cf6';
    const theme2 = company?.theme_color2 || '#6366f1';

    return (
        <div className="builder-container py-4 px-3" style={{ background: `linear-gradient(135deg, ${theme1} 0%, ${theme2} 100%)`, minHeight: '100vh' }}>
            <nav className="navbar navbar-expand-lg bg-white shadow-sm rounded-4 mb-4 px-4 py-2">
                <div className="container-fluid">
                    <div className="d-flex align-items-center gap-2">
                        {company?.logo && <img src={`http://localhost:5000/${company.logo}`} alt="logo" style={{height:'35px', borderRadius:'8px'}} />}
                        <span className="fw-bold">{company?.company_name?.toUpperCase()}</span>
                    </div>
                    <button className="btn btn-outline-secondary btn-sm rounded-pill" onClick={() => navigate(-1)}>
                        <i className="bi bi-arrow-left"></i> Back
                    </button>
                </div>
            </nav>

            <div className="row g-4">
                {/* LEFT: Form */}
                <div className="col-lg-8">
                    <div className="card border-0 shadow-lg rounded-4 overflow-hidden">
                        <div className="p-4 bg-white border-bottom">
                            <h5 className="fw-bold m-0 d-flex align-items-center gap-2">
                                <span style={{width:'5px', height:'25px', background:theme1, borderRadius:'10px'}}></span>
                                {id ? 'Edit' : 'Create'} Digital Business Card
                            </h5>
                        </div>
                        <form onSubmit={handleSubmit} className="p-4 bg-light">
                            {/* Form Sections (Profile, About, etc.) */}
                            <div className="bg-white p-4 rounded-4 shadow-sm mb-4">
                                <h6 className="fw-bold mb-3 text-uppercase small text-muted">Profile Information</h6>
                                <div className="row g-3">
                                    <div className="col-md-6">
                                        <label className="form-label small fw-bold">Employee Name</label>
                                        <input type="text" name="emp_name" className="form-control rounded-3" value={formData.emp_name} onChange={handleChange} required />
                                    </div>
                                    <div className="col-md-6">
                                        <label className="form-label small fw-bold">Designation</label>
                                        <input type="text" name="designation" className="form-control rounded-3" value={formData.designation} onChange={handleChange} />
                                    </div>
                                    <div className="col-md-6">
                                        <label className="form-label small fw-bold">Profile Photo (Double click preview to crop)</label>
                                        <input type="file" className="form-control rounded-3" onChange={handleFileChange} accept="image/*" />
                                    </div>
                                    <div className="col-md-6">
                                        <label className="form-label small fw-bold">Primary Phone</label>
                                        <input type="text" name="phone" className="form-control rounded-3" value={formData.phone} onChange={handleChange} />
                                    </div>
                                    <div className="col-md-6">
                                        <label className="form-label small fw-bold">Business Email</label>
                                        <input type="email" name="email" className="form-control rounded-3" value={formData.email} onChange={handleChange} />
                                    </div>
                                    <div className="col-md-6">
                                        <label className="form-label small fw-bold">WhatsApp Number</label>
                                        <input type="text" name="whatsapp" className="form-control rounded-3" value={formData.whatsapp} onChange={handleChange} />
                                    </div>
                                </div>
                            </div>

                            {/* About Section */}
                            <div className="bg-white p-4 rounded-4 shadow-sm mb-4">
                                <h6 className="fw-bold mb-3 text-uppercase small text-muted">Headline & About</h6>
                                <div className="mb-3">
                                    <label className="form-label small fw-bold">Headline</label>
                                    <input type="text" name="headline" className="form-control rounded-3" value={formData.headline} onChange={handleChange} placeholder="e.g. About Me" />
                                </div>
                                <div>
                                    <label className="form-label small fw-bold">Description</label>
                                    <textarea name="about" className="form-control rounded-3" rows="3" value={formData.about} onChange={handleChange} placeholder="Short profile..."></textarea>
                                </div>
                            </div>

                            {/* Social Links */}
                            <div className="bg-white p-4 rounded-4 shadow-sm mb-4">
                                <h6 className="fw-bold mb-3 text-uppercase small text-muted">Social Media Links</h6>
                                <div className="row g-3">
                                    {['linkedin', 'instagram', 'facebook', 'twitter'].map(social => (
                                        <div className="col-md-6" key={social}>
                                            <label className="form-label small fw-bold text-capitalize">{social}</label>
                                            <input type="url" name={social} className="form-control rounded-3" value={formData[social]} onChange={handleChange} placeholder="https://..." />
                                        </div>
                                    ))}
                                </div>
                            </div>

                            {/* Additional Links */}
                            <div className="bg-white p-4 rounded-4 shadow-sm mb-4">
                                <h6 className="fw-bold mb-3 text-uppercase small text-muted">Useful Links</h6>
                                <div className="row g-3">
                                    <div className="col-md-6">
                                        <label className="form-label small fw-bold">Website</label>
                                        <input type="url" name="website" className="form-control rounded-3" value={formData.website} onChange={handleChange} placeholder="https://..." />
                                    </div>
                                    <div className="col-md-6">
                                        <label className="form-label small fw-bold">Google Maps</label>
                                        <input type="url" name="maps" className="form-control rounded-3" value={formData.maps} onChange={handleChange} placeholder="https://..." />
                                    </div>
                                    <div className="col-12">
                                        <label className="form-label small fw-bold">Brochure / PDF Link</label>
                                        <input type="url" name="brochure" className="form-control rounded-3" value={formData.brochure} onChange={handleChange} placeholder="https://..." />
                                    </div>
                                </div>
                            </div>

                            <div className="sticky-bottom bg-white p-3 border-top rounded-bottom-4 text-end" style={{margin:'-1.5rem -1.5rem -1.5rem'}}>
                                <button type="submit" className="btn btn-primary px-5 py-2 fw-bold rounded-pill shadow" disabled={loading} style={{background:theme1, border:'none'}}>
                                    {loading ? 'Saving...' : 'Save Employee Card'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                {/* RIGHT: Premium Preview */}
                <div className="col-lg-4 d-none d-lg-block">
                    <div className="sticky-top" style={{top:'20px'}}>
                        <div className="preview-phone shadow-2xl mx-auto" style={{
                            width: '340px', 
                            height: '680px', 
                            background: '#1a1a1a', 
                            borderRadius: '40px', 
                            padding: '12px',
                            border: '4px solid #333'
                        }}>
                            <div className="bg-white h-100 rounded-4 overflow-auto custom-scrollbar" style={{borderRadius:'30px'}}>
                                {/* Hero Photo in Preview */}
                                <div 
                                    className="position-relative overflow-hidden cursor-pointer" 
                                    style={{ height: '220px', background: '#eee', cursor: 'pointer' }}
                                    onDoubleClick={() => {
                                        if (originalImage || photoPreview) {
                                            setImageToCrop(null);
                                            setTimeout(() => {
                                                setImageToCrop(originalImage || photoPreview);
                                                setShowCropper(true);
                                            }, 10);
                                        }
                                    }}
                                    title="Double click to crop"
                                >
                                    <img 
                                        src={photoPreview || `https://ui-avatars.com/api/?name=${encodeURIComponent(formData.emp_name || 'E')}&background=${theme1.replace('#','')}&color=ffffff&size=512`} 
                                        className="w-100 h-100" 
                                        style={{ objectFit: 'cover' }}
                                    />
                                    {/* Wave SVG in Preview */}
                                    <div style={{ position: 'absolute', bottom: '-1px', left: 0, width: '100%', lineHeight: 0 }}>
                                        <svg viewBox="0 0 500 150" preserveAspectRatio="none" style={{ height: '40px', width: '100%' }}>
                                            <path d="M0.00,49.98 C150.00,150.00 349.20,-49.98 500.00,49.98 L500.00,150.00 L0.00,150.00 Z" style={{ stroke: 'none', fill: '#fff' }}></path>
                                        </svg>
                                    </div>
                                </div>

                                <div className="p-3">
                                    <div className="border-start border-3 ps-2 mb-3" style={{ borderColor: '#ddd' }}>
                                        <h5 className="fw-bold mb-0" style={{ fontSize: '1.2rem' }}>{formData.emp_name || 'Employee Name'}</h5>
                                        <small className="text-muted d-block">{formData.designation || 'Designation'}</small>
                                        <small className="fst-italic" style={{ color: theme1 }}>{company?.company_name}</small>
                                    </div>
                                    <div className="d-grid mb-4">
                                        <div className="btn btn-sm text-white rounded-2 fw-bold" style={{background:theme1, fontSize:'10px'}}>SAVE CONTACT</div>
                                    </div>
                                    <div className="d-flex flex-column gap-2">
                                        <div className="d-flex align-items-center gap-2"><div className="rounded-circle d-flex align-items-center justify-content-center" style={{ width: '28px', height: '28px', background: theme1, color: '#fff', fontSize:'10px' }}><i className="bi bi-envelope-fill"></i></div><span style={{fontSize:'10px'}}>{formData.email || 'Email'}</span></div>
                                        <div className="d-flex align-items-center gap-2"><div className="rounded-circle d-flex align-items-center justify-content-center" style={{ width: '28px', height: '28px', background: theme1, color: '#fff', fontSize:'10px' }}><i className="bi bi-telephone-fill"></i></div><span style={{fontSize:'10px'}}>{formData.phone || 'Phone'}</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <p className="text-center text-white-50 mt-3 small">Double click image to crop ✂️</p>
                    </div>
                </div>
            </div>

            {/* Cropping Modal */}
            {showCropper && (
                <div className="position-fixed top-0 start-0 w-100 h-100 bg-black bg-opacity-75 d-flex flex-column align-items-center justify-content-center" style={{ zIndex: 9999 }}>
                    <div className="position-relative w-100 flex-grow-1">
                        <Cropper
                            image={imageToCrop}
                            crop={crop}
                            zoom={zoom}
                            aspect={1}
                            onCropChange={setCrop}
                            onZoomChange={setZoom}
                            onCropComplete={onCropComplete}
                        />
                    </div>
                    <div className="bg-white p-3 w-100 d-flex justify-content-center gap-3">
                        <button className="btn btn-secondary px-4" onClick={() => setShowCropper(false)}>Cancel</button>
                        <button className="btn btn-primary px-4" style={{background:theme1, border:'none'}} onClick={saveCroppedImage}>Crop & Save</button>
                    </div>
                </div>
            )}
        </div>
    );
};

export default EmployeeForm;

import React, { useState, useEffect } from 'react';
import axios from 'axios';
import { useParams } from 'react-router-dom';

const CardView = () => {
    const { companySlug, empCode } = useParams();
    const [employee, setEmployee] = useState(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const fetchCard = async () => {
            try {
                const res = await axios.get(`http://localhost:5000/api/public/card/${companySlug}/${empCode}`);
                setEmployee(res.data);
            } catch (err) {
                console.error('Card not found');
            } finally {
                setLoading(false);
            }
        };
        fetchCard();
    }, [companySlug, empCode]);

    if (loading) return <div className="text-center mt-5">Loading...</div>;
    if (!employee) return <div className="text-center mt-5 text-muted"><h4>Card Not Found</h4></div>;

    const theme1 = employee.theme_color1 || '#8b5cf6';
    const theme2 = employee.theme_color2 || '#6366f1';

    const socialIcons = {
        linkedin: 'bi-linkedin',
        instagram: 'bi-instagram',
        facebook: 'bi-facebook',
        twitter: 'bi-twitter-x'
    };

    return (
        <div className="card-view-premium" style={{ minHeight: '100vh', background: '#fff', maxWidth: '500px', margin: '0 auto', position: 'relative' }}>
            {/* Top Photo Section */}
            <div className="position-relative overflow-hidden" style={{ height: '400px' }}>
                <img 
                    src={employee.photo ? `http://localhost:5000/${employee.photo}` : `https://ui-avatars.com/api/?name=${encodeURIComponent(employee.emp_name)}&background=${theme1.replace('#','')}&color=ffffff&size=512`} 
                    style={{ width: '100%', height: '100%', objectFit: 'cover' }}
                    alt={employee.emp_name}
                />
                
                {/* Wavy Divider SVG */}
                <div style={{ position: 'absolute', bottom: '-1px', left: 0, width: '100%', lineHeight: 0 }}>
                    <svg viewBox="0 0 500 150" preserveAspectRatio="none" style={{ height: '80px', width: '100%' }}>
                        <path d="M0.00,49.98 C150.00,150.00 349.20,-49.98 500.00,49.98 L500.00,150.00 L0.00,150.00 Z" style={{ stroke: 'none', fill: '#fff' }}></path>
                    </svg>
                </div>
                <div style={{ position: 'absolute', bottom: '20px', left: 0, width: '100%', lineHeight: 0 }}>
                    <svg viewBox="0 0 500 150" preserveAspectRatio="none" style={{ height: '60px', width: '100%' }}>
                        <path d="M0.00,49.98 C150.00,150.00 349.20,-49.98 500.00,49.98 L500.00,150.00 L0.00,150.00 Z" style={{ stroke: 'none', fill: theme1, opacity: 0.3 }}></path>
                    </svg>
                </div>
            </div>

            {/* Info Section */}
            <div className="px-4 py-3">
                <div className="border-start border-3 ps-3 mb-4" style={{ borderColor: '#ddd' }}>
                    <h1 className="fw-bold mb-1" style={{ fontSize: '2.5rem', letterSpacing: '-0.5px' }}>{employee.emp_name}</h1>
                    <p className="text-muted fs-5 mb-1" style={{ fontWeight: '500' }}>{employee.designation}</p>
                    <p className="fst-italic" style={{ color: theme1, fontSize: '1.2rem' }}>{employee.company_name}</p>
                </div>

                {/* Primary Action */}
                <div className="d-grid gap-2 mb-5">
                    <button className="btn btn-primary rounded-3 py-3 fw-bold d-flex align-items-center justify-content-center gap-2 shadow-lg" style={{ background: theme1, border: 'none', fontSize: '1.1rem' }}>
                        <i className="bi bi-download"></i> SAVE CONTACT
                    </button>
                </div>

                {/* Contact Links */}
                <div className="contact-links d-flex flex-column gap-4 mb-5">
                    <a href={`mailto:${employee.email}`} className="d-flex align-items-center text-decoration-none text-dark">
                        <div className="rounded-circle d-flex align-items-center justify-content-center me-3 shadow-sm" style={{ width: '55px', height: '55px', background: theme1, color: '#fff' }}>
                            <i className="bi bi-envelope-fill fs-4"></i>
                        </div>
                        <span className="fs-6 fw-medium">{employee.email}</span>
                    </a>
                    <a href={`tel:${employee.phone}`} className="d-flex align-items-center text-decoration-none text-dark">
                        <div className="rounded-circle d-flex align-items-center justify-content-center me-3 shadow-sm" style={{ width: '55px', height: '55px', background: theme1, color: '#fff' }}>
                            <i className="bi bi-telephone-fill fs-4"></i>
                        </div>
                        <span className="fs-6 fw-medium">{employee.phone}</span>
                    </a>
                    {employee.whatsapp && (
                        <a href={`https://wa.me/${employee.whatsapp.replace(/[^0-9]/g, '')}`} className="d-flex align-items-center text-decoration-none text-dark">
                            <div className="rounded-circle d-flex align-items-center justify-content-center me-3 shadow-sm" style={{ width: '55px', height: '55px', background: '#25D366', color: '#fff' }}>
                                <i className="bi bi-whatsapp fs-4"></i>
                            </div>
                            <span className="fs-6 fw-medium">Chat on WhatsApp</span>
                        </a>
                    )}
                </div>

                {/* Social Links Grid */}
                <div className="row g-3">
                    {Object.keys(socialIcons).map(key => (
                        employee[key] && (
                            <div className="col-3 text-center" key={key}>
                                <a href={employee[key]} className="d-inline-flex align-items-center justify-content-center rounded-3 bg-light text-decoration-none shadow-sm" style={{ width: '60px', height: '60px', color: theme1 }}>
                                    <i className={`bi ${socialIcons[key]} fs-3`}></i>
                                </a>
                            </div>
                        )
                    ))}
                </div>

                {employee.about && (
                    <div className="mt-5 pt-4 border-top">
                        <h6 className="fw-bold mb-3">{employee.headline || 'About Me'}</h6>
                        <p className="text-muted" style={{ lineHeight: '1.7', fontSize: '0.95rem' }}>{employee.about}</p>
                    </div>
                )}
            </div>

            {/* Bottom Footer Branding */}
            <div className="text-center py-5 bg-light mt-5" style={{ borderRadius: '40px 40px 0 0' }}>
                <small className="text-muted">A digital business card from <strong>{employee.company_name}</strong></small>
            </div>
        </div>
    );
};

export default CardView;

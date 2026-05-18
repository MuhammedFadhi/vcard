import React, { useState, useEffect } from 'react';
import axios from 'axios';
import { useParams, useNavigate, Link } from 'react-router-dom';
import { QRCodeCanvas } from 'qrcode.react';

const Dashboard = () => {
    const { companySlug } = useParams();
    const [employees, setEmployees] = useState([]);
    const [company, setCompany] = useState(null);
    const [loading, setLoading] = useState(true);
    const navigate = useNavigate();

    axios.defaults.withCredentials = true;

    useEffect(() => {
        fetchData();
    }, [companySlug]);

    const fetchData = async () => {
        try {
            const authRes = await axios.get('/api/auth/me');
            if (!authRes.data.loggedIn || authRes.data.company.company_slug !== companySlug) {
                navigate('/');
                return;
            }
            setCompany(authRes.data.company);

            const empRes = await axios.get('/api/employees');
            setEmployees(empRes.data);
            setLoading(false);
        } catch (err) {
            navigate('/');
        }
    };

    const handleLogout = async () => {
        await axios.post('/api/auth/logout');
        navigate('/');
    };

    const handleDelete = async (id) => {
        if (window.confirm('Delete this employee card permanently?')) {
            try {
                const res = await axios.delete(`/api/employees/${id}`);
                if (res.data.success) {
                    fetchData();
                } else {
                    alert('Failed to delete: ' + (res.data.message || 'Unknown error'));
                }
            } catch (err) {
                console.error('Delete error:', err);
                alert('Error deleting employee: ' + (err.response?.data?.message || err.message));
            }
        }
    };

    const downloadQR = (empName, empCode) => {
        const canvas = document.getElementById(`qr-${empCode}`);
        if (canvas) {
            const pngUrl = canvas.toDataURL("image/png").replace("image/png", "image/octet-stream");
            let downloadLink = document.createElement("a");
            downloadLink.href = pngUrl;
            downloadLink.download = `${empName.replace(/\s+/g, '_')}_QR.png`;
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
        }
    };

    const copyLink = (code) => {
        const url = `${window.location.origin}/${companySlug}/${code}`;
        navigator.clipboard.writeText(url);
        alert('Card link copied to clipboard!');
    };

    if (loading) return <div className="text-white text-center mt-5">Loading...</div>;

    const theme1 = company?.theme_color1 || '#667eea';
    const theme2 = company?.theme_color2 || '#764ba2';

    return (
        <div className="dashboard-wrapper pb-5" style={{ background: `linear-gradient(135deg, ${theme1} 0%, ${theme2} 100%)` }}>
            {/* Navbar */}
            <nav className="navbar navbar-premium mb-0">
                <div className="container-fluid px-4 px-md-5">
                    <Link className="brand-wrap-premium d-flex align-items-center gap-2 text-decoration-none" to={`/${companySlug}`}>
                        {company?.logo && <img src={`/${company.logo}`} alt="logo" style={{height:'45px', width:'45px', borderRadius:'12px', objectFit:'cover'}} />}
                        <span className="fw-bold text-dark fs-5">{company?.company_name?.toUpperCase()}</span>
                    </Link>
                    <div className="nav-actions d-flex gap-2">
                        <button onClick={() => navigate(`/settings/${companySlug}`)} className="btn btn-light btn-sm px-3 rounded-3 border">
                            <i className="bi bi-gear"></i> Settings
                        </button>
                        <button className="btn btn-outline-danger btn-sm px-3 rounded-3" onClick={handleLogout}>
                            <i className="bi bi-box-arrow-right"></i> Logout
                        </button>
                    </div>
                </div>
            </nav>

            <div className="container-fluid px-md-5">
                {/* Page Header Bar */}
                <div className="page-header-premium mx-md-4">
                    <div className="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                        <div>
                            <h2 className="fw-bold text-dark mb-1" style={{fontSize: '2rem'}}>Employee Digital Cards</h2>
                            <p className="text-muted m-0">Create and manage employee business cards for {company?.company_name}.</p>
                        </div>
                        <button onClick={() => navigate('/create-employee')} className="create-btn-premium" style={{ background: `linear-gradient(135deg, ${theme1} 0%, ${theme2} 100%)` }}>
                            <i className="bi bi-plus-lg"></i> CREATE NEW CARD
                        </button>
                    </div>
                </div>

                {/* Employee Cards Grid */}
                <div className="row g-4 mb-5 px-md-4">
                    {employees.map((emp, index) => (
                        <div className="col-xl-3 col-lg-4 col-md-6" key={emp.id} style={{ animationDelay: `${0.1 * (index + 1)}s` }}>
                            <div className="card emp-card-premium text-center">
                                <div className="header-banner" style={{ background: `linear-gradient(135deg, ${theme1} 0%, ${theme2} 100%)` }}></div>
                                <div className="card-body pt-0 pb-4">
                                    {/* Avatar - Exact Match with 6px border */}
                                    <img 
                                        className="emp-photo-premium"
                                        src={emp.photo ? `/${emp.photo}` : `https://ui-avatars.com/api/?name=${encodeURIComponent(emp.emp_name)}&background=${theme1.replace('#','')}&color=ffffff&size=200`} 
                                        alt={emp.emp_name}
                                        onError={(e) => { e.target.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(emp.emp_name)}&background=${theme1.replace('#','')}&color=ffffff&size=200` }}
                                    />
                                    
                                    <h5 className="fw-bold mt-3 mb-1 text-dark" style={{fontSize:'1.3rem'}}>{emp.emp_name}</h5>
                                    <p className="text-muted small mb-4">{emp.designation}</p>
                                    
                                    {/* Row 1 Actions */}
                                    <div className="d-flex justify-content-center gap-2 mb-3">
                                        <Link to={`/${companySlug}/${emp.emp_code}`} className="btn btn-view-exact d-flex align-items-center gap-2" target="_blank" style={{fontSize:'0.85rem'}}>
                                            <i className="bi bi-eye"></i> View
                                        </Link>
                                        <Link to={`/edit-employee/${emp.id}`} className="btn btn-edit-exact d-flex align-items-center gap-2" style={{fontSize:'0.85rem'}}>
                                            <i className="bi bi-pencil"></i> Edit
                                        </Link>
                                    </div>

                                    {/* Row 2 Actions */}
                                    <div className="d-flex justify-content-center align-items-center gap-2 px-2">
                                        <button className="btn btn-copy-exact flex-grow-1 d-flex align-items-center justify-content-center gap-2" onClick={() => copyLink(emp.emp_code)} style={{fontSize:'0.85rem'}}>
                                            <i className="bi bi-clipboard"></i> Copy
                                        </button>
                                        <button className="btn btn-qr-exact flex-grow-1 d-flex align-items-center justify-content-center gap-2" onClick={() => downloadQR(emp.emp_name, emp.emp_code)} style={{fontSize:'0.85rem'}}>
                                            <i className="bi bi-qr-code"></i> QR Code
                                        </button>
                                        <button className="btn btn-delete-exact" onClick={() => handleDelete(emp.id)}>
                                            <i className="bi bi-trash"></i>
                                        </button>
                                    </div>

                                    {/* Hidden QR Canvas */}
                                    <div style={{display:'none'}}>
                                        <QRCodeCanvas id={`qr-${emp.emp_code}`} value={`${window.location.origin}/${companySlug}/${emp.emp_code}`} size={512} includeMargin={true}/>
                                    </div>
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </div>
    );
};

export default Dashboard;

import React from 'react';
import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import AuthPage from './pages/AuthPage';
import Dashboard from './pages/Dashboard';
import CardView from './pages/CardView';
import EmployeeForm from './pages/EmployeeForm';
import Settings from './pages/Settings';
import EmployeeManage from './pages/EmployeeManage';
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap-icons/font/bootstrap-icons.css';
import './index.css';

function App() {
  return (
    <Router>
      <Routes>
        {/* Auth */}
        <Route path="/" element={<AuthPage />} />
        
        {/* Dashboard - Supporting both styles */}
        <Route path="/dashboard/:companySlug" element={<Dashboard />} />
        <Route path="/:companySlug/dashboard" element={<Dashboard />} />
        
        {/* Settings */}
        <Route path="/settings/:companySlug" element={<Settings />} />
        
        {/* Employee Management */}
        <Route path="/:companySlug/manage" element={<EmployeeManage />} />
        <Route path="/create-employee" element={<EmployeeForm />} />
        <Route path="/edit-employee/:id" element={<EmployeeForm />} />
        
        {/* Public Card View (Catch-all for slugs) */}
        <Route path="/:companySlug" element={<Dashboard />} />
        <Route path="/:companySlug/:empCode" element={<CardView />} />
      </Routes>
    </Router>
  );
}

export default App;

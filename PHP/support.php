<?php
require_once 'db_connect.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['professor_id']) && !isset($_SESSION['student_id'])) {
    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support & Contact - SADD LMS</title>
    <link rel="icon" href="../IMAGES/BUPC_Logo.png" type="image/png">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        /* Support Container */
.support-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 24px;
}

/* Support Card */
.support-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    padding: 24px;
    margin-bottom: 24px;
    transition: box-shadow 0.3s ease;
}

.support-card:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

/* Header Styles */
.support-header {
    display: flex;
    align-items: center;
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 1px solid #e0e0e0;
}

.support-header i {
    font-size: 32px;
    margin-right: 16px;
    color: #0066cc;
    transition: transform 0.3s ease;
}

.support-header:hover i {
    transform: scale(1.1);
}

.support-header h1 {
    margin: 0;
    font-size: 24px;
    color: #202124;
    font-weight: 500;
}

/* Development Team Section */
.dev-team {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 24px;
    margin-top: 24px;
}

.dev-card {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 20px;
    transition: all 0.3s ease;
    border: 1px solid transparent;
}

.dev-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    border-color:rgb(0, 16, 238);
}

.dev-name {
    font-size: 18px;
    font-weight: 500;
    color:rgb(33, 32, 36);
    margin-bottom: 8px;
}

.dev-role {
    color: #5f6368;
    font-size: 14px;
    margin-bottom: 16px;
    padding: 4px 8px;
    background: rgba(0, 102, 204, 0.1);
    border-radius: 4px;
    display: inline-block;
}

/* Contact Information */
.contact-info {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.contact-item {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #5f6368;
    font-size: 14px;
    padding: 8px;
    border-radius: 4px;
    transition: background-color 0.2s;
}

.contact-item:hover {
    background-color: rgba(0, 102, 204, 0.05);
}

.contact-item i {
    font-size: 18px;
    color: #0066cc;
}

.contact-item a {
    color: #0066cc;
    text-decoration: none;
    transition: color 0.2s;
}

.contact-item a:hover {
    color: #004d99;
    text-decoration: underline;
}

/* Support Sections */
.support-section {
    margin-top: 32px;
    padding: 24px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.support-section h2 {
    color: #202124;
    font-size: 20px;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.support-section h2 i {
    color: #0066cc;
    font-size: 24px;
}

.support-section p {
    color: #5f6368;
    line-height: 1.6;
    margin-bottom: 16px;
}

/* Footer Styles */
.footer {
    background: #f8f9fa;
    padding: 24px;
    text-align: center;
    border-top: 1px solid #e0e0e0;
    margin-top: 40px;
    border-radius: 8px;
    transition: transform 0.3s ease;
}

.footer:hover {
    transform: translateY(-2px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.footer p {
    color: #5f6368;
    font-size: 14px;
    margin: 8px 0;
    line-height: 1.5;
}

.footer strong {
    color: #202124;
    font-weight: 500;
}

/* Emergency Contact Highlight */
.emergency-contact {
    background: #fef7f6;
    border-left: 4px solid #d93025;
    padding: 16px;
    margin-top: 16px;
    border-radius: 4px;
}

.emergency-contact .contact-item {
    background: transparent;
}

.emergency-contact i {
    color: #d93025;
}

.emergency-contact a {
    color: #d93025;
}

.emergency-contact a:hover {
    color: #a50e0e;
}

/* Office Hours Styling */
.office-hours .contact-item {
    border-left: 3px solid transparent;
    padding-left: 12px;
}

.office-hours .contact-item:hover {
    border-left-color: #0066cc;
}

/* Responsive Design */
@media (max-width: 768px) {
    .support-container {
        padding: 16px;
    }

    .dev-team {
        grid-template-columns: 1fr;
    }

    .support-section {
        padding: 16px;
    }

    .footer {
        padding: 16px;
        margin-top: 24px;
    }

    .support-header h1 {
        font-size: 20px;
    }

    .support-header i {
        font-size: 24px;
    }
}

/* Animations */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.support-section {
    animation: fadeIn 0.3s ease-out;
}

/* Accessibility */
@media (prefers-reduced-motion: reduce) {
    * {
        animation: none !important;
        transition: none !important;
    }
}

/* Print Styles */
@media print {
    .support-container {
        max-width: 100%;
        padding: 0;
    }

    .support-card,
    .support-section {
        box-shadow: none;
        border: 1px solid #e0e0e0;
    }

    .footer {
        border-top: 2px solid #202124;
    }
} 
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Navigation menu -->
        

        <main class="main-content">
            <div class="support-container">
                <div class="support-card">
                    <div class="support-header">
                        <i class="material-icons">support_agent</i>
                        <h1>Support & Contact Information</h1>
                    </div>

                    <div class="support-section">
                        <h2>Development Team</h2>
                        <p>Our dedicated team is here to help you with any technical issues or questions you may have about the SADD Learning Management System.</p>
                        
                        <div class="dev-team">
                            <div class="dev-card">
                                <div class="dev-name">Faith Sanado</div>
                                <div class="dev-role">Lead Developer</div>
                                <div class="contact-info">
                                    <div class="contact-item">
                                        <i class="material-icons">email</i>
                                        <a href="mailto:john.doe@sadd.edu">faith.san@sadd.edu</a>
                                    </div>
                                    <div class="contact-item">
                                        <i class="material-icons">phone</i>
                                        <span>+63 912 345 6789</span>
                                    </div>
                                </div>
                            </div>

                            <div class="dev-card">
                                <div class="dev-name">James Sariba</div>
                                <div class="dev-role">Frontend Developer</div>
                                <div class="contact-info">
                                    <div class="contact-item">
                                        <i class="material-icons">email</i>
                                        <a href="mailto:jane.smith@sadd.edu">james.sariba@sadd.edu</a>
                                    </div>
                                    <div class="contact-item">
                                        <i class="material-icons">phone</i>
                                        <span>+63 923 456 7890</span>
                                    </div>
                                </div>
                            </div>

                            <div class="dev-card">
                                <div class="dev-name">Jeannie Fetil</div>
                                <div class="dev-role">Backend Developer</div>
                                <div class="contact-info">
                                    <div class="contact-item">
                                        <i class="material-icons">email</i>
                                        <a href="mailto:mike.johnson@sadd.edu">jen.Fetil@sadd.edu</a>
                                    </div>
                                    <div class="contact-item">
                                        <i class="material-icons">phone</i>
                                        <span>+63 934 567 8901</span>
                                    </div>
                                </div>
                            </div>
                            <div class="dev-card">
                                <div class="dev-name">Mhelarry Valeza</div>
                                <div class="dev-role">Backend Developer</div>
                                <div class="contact-info">
                                    <div class="contact-item">
                                        <i class="material-icons">email</i>
                                        <a href="mailto:mike.johnson@sadd.edu">mhel.Val@sadd.edu</a>
                                    </div>
                                    <div class="contact-item">
                                        <i class="material-icons">phone</i>
                                        <span>+63 942 567 8701</span>
                                    </div>
                                </div>
                            </div>
                            <div class="dev-card">
                                <div class="dev-name">Rence Fernandez</div>
                                <div class="dev-role">Pancit Canton Provider & chief Logistics</div>
                                <div class="contact-info">
                                    <div class="contact-item">
                                        <i class="material-icons">email</i>
                                        <a href="mailto:mike.johnson@sadd.edu">Rence.Fernandez@sadd.edu</a>
                                    </div>
                                    <div class="contact-item">
                                        <i class="material-icons">phone</i>
                                        <span>+63 976 567 9901</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="support-section">
                        <h2>Office Hours</h2>
                        <p>Our support team is available during the following hours:</p>
                        <div class="contact-info">
                            <div class="contact-item">
                                <i class="material-icons">schedule</i>
                                <span>Monday - Friday: 8:00 AM - 5:00 PM</span>
                            </div>
                            <div class="contact-item">
                                <i class="material-icons">schedule</i>
                                <span>Saturday: 9:00 AM - 12:00 PM</span>
                            </div>
                            <div class="contact-item">
                                <i class="material-icons">schedule</i>
                                <span>Sunday: Closed</span>
                            </div>
                        </div>
                    </div>

                    <div class="support-section">
                        <h2>Emergency Contact</h2>
                        <p>For urgent technical issues outside office hours:</p>
                        <div class="contact-info">
                            <div class="contact-item">
                                <i class="material-icons">emergency</i>
                                <span>Emergency Hotline: +63 999 999 9999</span>
                            </div>
                            <div class="contact-item">
                                <i class="material-icons">email</i>
                                <a href="mailto:emergency@sadd.edu">emergency@sadd.edu</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html> 

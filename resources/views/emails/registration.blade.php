<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Welcome to AlephâOne</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            max-width: 200px;
            height: auto;
        }
        .welcome-section {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .feedback-section {
            background-color: #e3f2fd;
            border: 1px solid #2196f3;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 12px;
        }
        .table th, .table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .table td {
            background-color: #fff;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            font-size: 14px;
            color: #6c757d;
            text-align: center;
        }
        .highlight {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 4px;
            padding: 15px;
            margin: 20px 0;
            color: #856404;
        }
        .contact-info {
            background-color: #d1ecf1;
            border: 1px solid #bee5eb;
            border-radius: 4px;
            padding: 15px;
            margin: 20px 0;
            color: #0c5460;
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ asset('images/aleph-one-logo.png') }}" alt="Aleph∞One" class="logo">
        <h1>Welcome to AlephâOne</h1>
    </div>

    <div class="welcome-section">
        <p>Hello {{ $userName }},</p>

        <p>Thank you for registering with <strong>AlephâOne</strong>, an all-in-one web application for managing, analyzing and sharing multi-sectoral epidemiological data.</p>

        <p>Your account has been successfully created and verified. You can now access all the features of our platform.</p>

        <p><strong>Please note:</strong> You are now working in the live platform environment. Please handle project data according to your organization's data governance and access policies.</p>
    </div>

    <div class="highlight">
        <strong>Important:</strong> By reading this email, you confirm your willingness to participate in the trialing of the AlephâOne application. Your feedback is invaluable to us as we continue to improve and develop the platform.
    </div>

    <div class="contact-info">
        <p><strong>We value your feedback!</strong></p>
        <p>Please let us know about your user experience by emailing us at: <strong>feedback@aleph-one.com</strong></p>
        <p>Your insights help us improve the application for all users.</p>
    </div>

    <div class="feedback-section">
        <h3>Feedback Template</h3>
        <p>To help us better understand and address any issues you encounter, you are welcome to use the following template when reporting problems:</p>

        <table class="table">
            <thead>
                <tr>
                    <th>Issue Type</th>
                    <th>Section/Module</th>
                    <th>Functionality</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Error</td>
                    <td>Animal Sample</td>
                    <td>Registration</td>
                    <td>Editing button is triggering a 500 error when clicked</td>
                </tr>
                <tr>
                    <td>Missing Feature</td>
                    <td>Experiment</td>
                    <td>Dashboard</td>
                    <td>An additional bar chart representing xx% of the total number of samples would be useful</td>
                </tr>
                <tr>
                    <td>Privacy</td>
                    <td>Human Sample</td>
                    <td>List View</td>
                    <td>Private information are displayed in the list view</td>
                </tr>
                <tr>
                    <td>Performance</td>
                    <td>Experiment</td>
                    <td>Registration</td>
                    <td>Registration of multiple samples is slow</td>
                </tr>
            </tbody>
        </table>

        <p><strong>Issue Types:</strong> Error, Missing Feature, Privacy, Performance, Other</p>
        <p><strong>Sections/Modules:</strong> Samples, Experiment, Storage, Literature, Team, Documents, Other</p>
        <p><strong>Functionalities:</strong> Registration, List, Dashboard, Profile, Other</p>
    </div>

    <p>We're excited to have you on board and look forward to your contributions to the optimization of AlephâOne!</p>

    <p>Best regards,<br>

    The Aleph∞One Team</p>

    <div class="footer">
        <p>&copy; {{ date('Y') }} Aleph∞One. All rights reserved.</p>
    </div>
</body>
</html> 
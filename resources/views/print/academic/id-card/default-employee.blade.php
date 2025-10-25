<style>
    .id-card {
        height: 9.9cm;
        width: 6.7cm;
        border: 2px solid #000;
        border-radius: 10px;
        background: #fff;
        box-shadow: 0px 0px 10px 0px rgba(0, 0, 0, 0.1);
        padding: 15px;
    }

    .header {
        text-align: center;
        margin-bottom: 10px;
    }

    .school-logo {
        width: 60px;
    }

    .school-info h1 {
        font-size: 14px;
        margin: 5px 0;
        color: #800000;
        text-transform: uppercase;
    }

    .school-info h2 {
        font-size: 11px;
        margin: 3px 0;
        color: #d32f2f;
        text-transform: uppercase;
    }

    .school-info h3 {
        font-size: 14px;
        margin: 3px 0;
        color: #616161;
    }

    .photo {
        text-align: center;
        margin-bottom: 5px;
    }

    .photo img {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        border: 2px solid #d32f2f;
    }

    .employee-info h2 {
        font-size: 14px;
        color: #1976d2;
        text-align: center;
        margin: 5px 0;
    }

    .employee-info h3 {
        font-size: 12px;
        color: #800000;
        text-align: center;
        margin: 5px 0;
        background-color: #eceff1;
        padding: 3px 0;
        border-radius: 3px;
    }

    .employee-info p {
        font-size: 11px;
        margin: 5px 0;
        color: #424242;
    }

    .signature {
        text-align: center;
        margin-top: 10px;
    }

    .signature p {
        margin: 0;
        font-size: 11px;
        color: #1976d2;
    }

    .signature-img {
        width: 100px;
        margin-top: 5px;
    }
</style>

<div class="id-card">
    <div class="header">
        <img src="#INSTITUTE_LOGO#" alt="School Logo" class="school-logo">
        <div class="school-info">
            <h1>#INSTITUTE_NAME#</h1>
            <h2>#INSTITUTE_ADDRESS#</h2>
        </div>
    </div>
    <div style="display: flex; align-items: center; ">
        <div class="photo">
            <img src="#PHOTO#" alt="Student Photo">
        </div>
        <div class="employee-info" style="margin-left: 10px;">
            <p style="font-size: 12px; font-weight: bold;">#NAME#</p>
            <p><strong>Employee Code:</strong> #EMPLOYEE_CODE#</p>
            <p><strong>Designation:</strong> #DESIGNATION#</p>
            <p><strong>Department:</strong> #DEPARTMENT#</p>
        </div>
    </div>
    <div class="employee-info">
        <p><strong>Father Name:</strong> #FATHER_NAME#</p>
        <p><strong>DOB:</strong> #DOB#</p>
        <p><strong>Address:</strong> #ADDRESS#</p>
        <p><strong>Mo:</strong> #CONTACT_NUMBER#</p>
        <p><strong>Blood Group:</strong> #BLOOD_GROUP#</p>
    </div>
    <div class="signature">
        <p>Principal</p>
        <img src="#SIGNATURE#" alt="Signature" class="signature-img">
    </div>
</div>

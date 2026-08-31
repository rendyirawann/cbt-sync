<input type="hidden" name="hidden_id" id="hidden_id" value="{{ $user->id }}" />



<!--begin::Input group for Nama Lengkap (User)-->
<div class="fv-row mb-7">
    <!--begin::Label-->
    <label class="required fs-5 fw-bold mb-2">Nama Lengkap</label>
    <!--end::Label-->
    <!--begin::Input-->
    <input type="text" name="name" id="editName" class="form-control  mb-3 mb-lg-0" placeholder="Nama Lengkap"
        value="{{ $user->name }}" />
    <span class="text-danger error-text name_error_edit"></span>
    <!--end::Input-->
</div>
<!--end::Input group-->
<!--begin::Input group for Nama Lengkap (User)-->
<div class="fv-row mb-7">
    <!--begin::Label-->
    <label class="required fs-5 fw-bold mb-2">No. WhatsApp</label>
    <!--end::Label-->
    <!--begin::Input-->
    <input type="text" name="no_wa" id="editNo_wa" class="form-control  mb-3 mb-lg-0" placeholder="Nomor WhatsApp"
        value="{{ $user->no_wa }}" />
    <span class="text-danger error-text no_wa_error_edit"></span>
    <!--end::Input-->
</div>
<!--end::Input group-->

<!--begin::Input group for Email (User)-->
<div class="fv-row mb-7">
    <!--begin::Label-->
    <label class="required fs-5 fw-bold mb-2">Email</label>
    <!--end::Label-->
    <!--begin::Input-->
    <input type="text" name="email" id="editemail" class="form-control mb-3 mb-lg-0" placeholder="Email"
        value="{{ $user->email }}" />
    <span class="text-danger error-text email_error_edit"></span>
    <!--end::Input-->
</div>
<!--end::Input group-->

@if($user->hasRole('Siswa') && $user->student)
<div class="separator separator-dashed my-10"></div>
<h3 class="fw-bold mb-7">Parent Information</h3>

<div class="fv-row mb-7">
    <label class="required fs-5 fw-bold mb-2">Parent Name</label>
    <input type="text" name="parent_name" class="form-control mb-3 mb-lg-0" placeholder="Parent Name"
        value="{{ $user->student->parent_name }}" />
    <span class="text-danger error-text parent_name_error_edit"></span>
</div>

<div class="fv-row mb-7">
    <label class="required fs-5 fw-bold mb-2">Parent Email</label>
    <input type="email" name="parent_email" class="form-control mb-3 mb-lg-0" placeholder="Parent Email"
        value="{{ $user->student->parent_email }}" />
    <span class="text-danger error-text parent_email_error_edit"></span>
</div>

<div class="fv-row mb-7">
    <label class="required fs-5 fw-bold mb-2">Parent Phone (WA)</label>
    <input type="text" name="parent_phone" class="form-control mb-3 mb-lg-0" placeholder="Parent Phone"
        value="{{ $user->student->parent_phone }}" />
    <span class="text-danger error-text parent_phone_error_edit"></span>
</div>
@endif

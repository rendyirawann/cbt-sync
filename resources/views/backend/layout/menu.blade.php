					<!--begin::Aside menu-->
					<div class="aside-menu flex-column-fluid">
						<!--begin::Aside Menu-->
						<div class="hover-scroll-overlay-y my-5 mx-2" id="kt_aside_menu_wrapper" data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-height="auto" data-kt-scroll-dependencies="#kt_aside_footer" data-kt-scroll-wrappers="#kt_aside, #kt_aside_menu" data-kt-scroll-offset="2px">
							<!--begin::Menu-->
							<div class="menu menu-column menu-sub-indention menu-active-bg menu-state-primary menu-title-gray-700 fs-6 menu-rounded w-100 fw-semibold" id="#kt_aside_menu" data-kt-menu="true">
								
								<!--begin:Menu item-->
								<div class="menu-item pt-5">
									<!--begin:Menu content-->
									<div class="menu-content">
										<span class="menu-heading fw-bold text-uppercase fs-7">Main</span>
									</div>
									<!--end:Menu content-->
								</div>
								<!--end:Menu item-->

								<!--begin:Menu item-->
								<div class="menu-item">
									<!--begin:Menu link-->
									@php
										$dashboardRoute = auth()->user()->hasRole('Siswa') ? 'student.dashboard' : 'dashboard';
									@endphp
									<a class="menu-link {{ request()->routeIs($dashboardRoute) ? 'active' : '' }}" href="{{ route($dashboardRoute) }}">
										<span class="menu-icon">
											<i class="ki-duotone ki-element-11 fs-2">
												<span class="path1"></span>
												<span class="path2"></span>
												<span class="path3"></span>
												<span class="path4"></span>
											</i>
										</span>
										<span class="menu-title">Dashboard</span>
									</a>
									<!--end:Menu link-->
								</div>
								<!--end:Menu item-->

								<!--begin:Menu item-->
								<div class="menu-item pt-5">
									<!--begin:Menu content-->
									<div class="menu-content">
										<span class="menu-heading fw-bold text-uppercase fs-7">Account</span>
									</div>
									<!--end:Menu content-->
								</div>
								<!--end:Menu item-->

								<!--begin:Menu item-->
								<div class="menu-item">
									<!--begin:Menu link-->
									@php
										$profileRoute = auth()->user()->hasRole('Siswa') ? 'student.account.index' : 'account.index';
									@endphp
									<a class="menu-link {{ request()->routeIs($profileRoute) ? 'active' : '' }}" href="{{ route($profileRoute) }}">
										<span class="menu-icon">
											<i class="ki-duotone ki-address-book fs-2">
												<span class="path1"></span>
												<span class="path2"></span>
												<span class="path3"></span>
											</i>
										</span>
										<span class="menu-title">My Profile</span>
									</a>
									<!--end:Menu link-->
								</div>
								<!--end:Menu item-->

								<!--begin:Menu item-->
								<div class="menu-item pt-5">
									<!--begin:Menu content-->
									<div class="menu-content">
										<span class="menu-heading fw-bold text-uppercase fs-7">Akademik</span>
									</div>
									<!--end:Menu content-->
								</div>
								<!--end:Menu item-->

								@if(false) {{-- CBT-SYNC: non-CBT --}}
<!--begin:Menu item-->
								<div class="menu-item">
									@php
										$moduleRoute = auth()->user()->hasRole('Siswa') ? 'student.learning-modules.index' : 'learning-modules.index';
									@endphp
									<a class="menu-link {{ request()->routeIs($moduleRoute) ? 'active' : '' }}" href="{{ route($moduleRoute) }}">
										<span class="menu-icon">
											<i class="ki-outline ki-book-open fs-2"></i>
										</span>
										<span class="menu-title">Modul Pembelajaran</span>
									</a>
								</div>
								<!--end:Menu item-->
@endif

								@if(false) {{-- CBT-SYNC: non-CBT --}}
<!--begin:Menu item-->
								<div class="menu-item">
									@php
										$assignmentRoute = auth()->user()->hasRole('Siswa') ? 'student.assignments.index' : 'assignments.index';
									@endphp
									<a class="menu-link {{ request()->routeIs($assignmentRoute) ? 'active' : '' }}" href="{{ route($assignmentRoute) }}">
										<span class="menu-icon">
											<i class="ki-outline ki-notepad-edit fs-2"></i>
										</span>
										<span class="menu-title">Penugasan Siswa</span>
									</a>
								</div>
								<!--end:Menu item-->
@endif

                                @if(auth()->user()->hasRole('Superadmin') || auth()->user()->hasRole('Guru') || auth()->user()->hasRole('Kepala Sekolah'))
								<!--begin:Menu item-->
								<div class="menu-item">
									<a class="menu-link {{ request()->routeIs('exams.*') || request()->routeIs('exam-sessions.*') || request()->routeIs('exam-attempts.*') ? 'active' : '' }}" href="{{ route('exams.index') }}">
										<span class="menu-icon">
											<i class="ki-outline ki-questionnaire-tablet fs-2"></i>
										</span>
										<span class="menu-title">Ujian / CBT</span>
									</a>
								</div>
								<!--end:Menu item-->

								@if(false) {{-- CBT-SYNC: non-CBT --}}
<!--begin:Menu item-->
								<div class="menu-item">
									<a class="menu-link {{ request()->routeIs('schedules.*') ? 'active' : '' }}" href="{{ route('schedules.index') }}">
										<span class="menu-icon">
											<i class="ki-outline ki-calendar-8 fs-2"></i>
										</span>
										<span class="menu-title">Jadwal Pelajaran</span>
									</a>
								</div>
								<!--end:Menu item-->
@endif

								@if(false) {{-- CBT-SYNC: non-CBT --}}
<!--begin:Menu item-->
								<div class="menu-item">
									<a class="menu-link {{ request()->routeIs('attendances.*') ? 'active' : '' }}" href="{{ route('attendances.index') }}">
										<span class="menu-icon">
											<i class="ki-outline ki-time fs-2"></i>
										</span>
										<span class="menu-title">Absensi Siswa</span>
									</a>
								</div>
								<!--end:Menu item-->
@endif

								@if(false) {{-- CBT-SYNC: non-CBT --}}
<!--begin:Menu item-->
								<div class="menu-item">
									<a class="menu-link {{ request()->routeIs('portal.leaderboard') ? 'active' : '' }}" href="{{ route('portal.leaderboard') }}">
										<span class="menu-icon">
											<i class="ki-outline ki-cup fs-2"></i>
										</span>
										<span class="menu-title">Leaderboard & Lencana</span>
									</a>
								</div>
								<!--end:Menu item-->
@endif

								@if(false) {{-- CBT-SYNC: non-CBT --}}
<!--begin:Menu item-->
								<div class="menu-item">
									<a class="menu-link {{ request()->routeIs('admin.analytics.index') ? 'active' : '' }}" href="{{ route('admin.analytics.index') }}">
										<span class="menu-icon">
											<i class="ki-outline ki-chart-line fs-2"></i>
										</span>
										<span class="menu-title">Laporan Aktivitas</span>
									</a>
								</div>
								<!--end:Menu item-->
@endif

								<!--begin:Menu item-->
								<div class="menu-item">
									<a class="menu-link {{ request()->routeIs('admin.rapor.*') ? 'active' : '' }}" href="{{ route('admin.rapor.index') }}">
										<span class="menu-icon">
											<i class="ki-outline ki-award fs-2"></i>
										</span>
										<span class="menu-title">Raport Hasil Ujian</span>
									</a>
								</div>
								<!--end:Menu item-->

								@if(false) {{-- CBT-SYNC: non-CBT --}}
<!--begin:Menu item-->
								<div class="menu-item">
									<a class="menu-link {{ request()->routeIs('books.*') ? 'active' : '' }}" href="{{ route('books.index') }}">
										<span class="menu-icon">
											<i class="ki-outline ki-book fs-2"></i>
										</span>
										<span class="menu-title">Manajemen Perpustakaan</span>
									</a>
								</div>
								<!--end:Menu item-->
@endif
                                @endif

                                @if(auth()->user()->hasRole('Siswa'))
								<!--begin:Menu item-->
								<div class="menu-item">
									<a class="menu-link {{ request()->routeIs('student.attendance') ? 'active' : '' }}" href="{{ route('student.attendance') }}">
										<span class="menu-icon">
											<i class="ki-outline ki-time fs-2"></i>
										</span>
										<span class="menu-title">Absensi Saya</span>
									</a>
								</div>
								<!--end:Menu item-->

								<!--begin:Menu item-->
								<div class="menu-item">
									<a class="menu-link {{ request()->routeIs('student.timetable') ? 'active' : '' }}" href="{{ route('student.timetable') }}">
										<span class="menu-icon">
											<i class="ki-outline ki-calendar-tick fs-2"></i>
										</span>
										<span class="menu-title">Jadwal Pelajaran</span>
									</a>
								</div>
								<!--end:Menu item-->

								<!--begin:Menu item-->
								<div class="menu-item">
									<a class="menu-link {{ request()->routeIs('student.chat.*') ? 'active' : '' }}" href="{{ route('student.chat.index') }}">
										<span class="menu-icon">
											<i class="ki-outline ki-messages fs-2"></i>
										</span>
										<span class="menu-title">Pesan Internal</span>
									</a>
								</div>
								<!--end:Menu item-->

								@if(false) {{-- CBT-SYNC: non-CBT --}}
<!--begin:Menu item-->
								<div class="menu-item">
									<a class="menu-link {{ request()->routeIs('portal.leaderboard') ? 'active' : '' }}" href="{{ route('portal.leaderboard') }}">
										<span class="menu-icon">
											<i class="ki-outline ki-cup fs-2"></i>
										</span>
										<span class="menu-title">Leaderboard & Lencana</span>
									</a>
								</div>
								<!--end:Menu item-->
@endif

								<!--begin:Menu item-->
								<div class="menu-item">
									<a class="menu-link {{ request()->routeIs('student.library.index') ? 'active' : '' }}" href="{{ route('student.library.index') }}">
										<span class="menu-icon">
											<i class="ki-outline ki-book fs-2"></i>
										</span>
										<span class="menu-title">Perpustakaan Saya</span>
									</a>
								</div>
								<!--end:Menu item-->

								<!--begin:Menu item-->
								<div class="menu-item">
									<a class="menu-link {{ request()->routeIs('student.rapor.*') ? 'active' : '' }}" href="{{ route('student.rapor.index') }}">
										<span class="menu-icon">
											<i class="ki-outline ki-award fs-2"></i>
										</span>
										<span class="menu-title">Raport Hasil Ujian</span>
									</a>
								</div>
								<!--end:Menu item-->
                                @endif

                                @can('view_data_master')
								<!--begin:Menu item-->
								<div class="menu-item">
									<a class="menu-link {{ request()->routeIs('enrollments.*') ? 'active' : '' }}" href="{{ route('enrollments.index') }}">
										<span class="menu-icon">
											<i class="ki-outline ki-user-square fs-2"></i>
										</span>
										<span class="menu-title">Rombongan Belajar</span>
									</a>
								</div>
								<!--end:Menu item-->

								<!--begin:Menu item-->
								<div class="menu-item pt-5">
									<!--begin:Menu content-->
									<div class="menu-content">
										<span class="menu-heading fw-bold text-uppercase fs-7">Master Data</span>
									</div>
									<!--end:Menu content-->
								</div>
								<!--end:Menu item-->

								<!--begin:Menu item-->
								<div data-kt-menu-trigger="click" class="menu-item menu-accordion {{ request()->routeIs('schools.*') || request()->routeIs('academic-years.*') || request()->routeIs('subjects.*') || request()->routeIs('class-rooms.*') || request()->routeIs('teachers.*') || request()->routeIs('students.*') ? 'here show' : '' }}">
									<!--begin:Menu link-->
									<span class="menu-link">
										<span class="menu-icon">
											<i class="ki-duotone ki-abstract-26 fs-2">
												<span class="path1"></span>
												<span class="path2"></span>
												<span class="path3"></span>
												<span class="path4"></span>
											</i>
										</span>
										<span class="menu-title">Master LMS</span>
										<span class="menu-arrow"></span>
									</span>
									<!--end:Menu link-->
									<!--begin:Menu sub-->
									<div class="menu-sub menu-sub-accordion">
										<div class="menu-item"><a class="menu-link {{ request()->routeIs('schools.*') ? 'active' : '' }}" href="{{ route('schools.index') }}"><span class="menu-bullet"><span class="bullet bullet-dot"></span></span><span class="menu-title">Data Sekolah</span></a></div>
										<div class="menu-item"><a class="menu-link {{ request()->routeIs('academic-years.*') ? 'active' : '' }}" href="{{ route('academic-years.index') }}"><span class="menu-bullet"><span class="bullet bullet-dot"></span></span><span class="menu-title">Tahun Ajaran</span></a></div>
										<div class="menu-item"><a class="menu-link {{ request()->routeIs('subjects.*') ? 'active' : '' }}" href="{{ route('subjects.index') }}"><span class="menu-bullet"><span class="bullet bullet-dot"></span></span><span class="menu-title">Mata Pelajaran</span></a></div>
										<div class="menu-item"><a class="menu-link {{ request()->routeIs('class-rooms.*') ? 'active' : '' }}" href="{{ route('class-rooms.index') }}"><span class="menu-bullet"><span class="bullet bullet-dot"></span></span><span class="menu-title">Ruang Kelas</span></a></div>
										<div class="menu-item"><a class="menu-link {{ request()->routeIs('teachers.*') ? 'active' : '' }}" href="{{ route('teachers.index') }}"><span class="menu-bullet"><span class="bullet bullet-dot"></span></span><span class="menu-title">Data Guru</span></a></div>
										<div class="menu-item"><a class="menu-link {{ request()->routeIs('students.*') ? 'active' : '' }}" href="{{ route('students.index') }}"><span class="menu-bullet"><span class="bullet bullet-dot"></span></span><span class="menu-title">Data Siswa</span></a></div>
										<div class="menu-item"><a class="menu-link {{ request()->routeIs('teaching-assignments.*') ? 'active' : '' }}" href="{{ route('teaching-assignments.index') }}"><span class="menu-bullet"><span class="bullet bullet-dot"></span></span><span class="menu-title">Penugasan Guru</span></a></div>
									</div>
									<!--end:Menu sub-->
								</div>
								<!--end:Menu item-->

								@endcan

								@can('view_resources')
								<!--begin:Menu item-->
								<div class="menu-item pt-5">
									<!--begin:Menu content-->
									<div class="menu-content">
										<span class="menu-heading fw-bold text-uppercase fs-7">Administration</span>
									</div>
									<!--end:Menu content-->
								</div>
								<!--end:Menu item-->

								<!--begin:Menu item-->
								<div data-kt-menu-trigger="click" class="menu-item menu-accordion {{ request()->routeIs('users.*') || request()->routeIs('roles.*') ? 'here show' : '' }}">
									<!--begin:Menu link-->
									<span class="menu-link">
										<span class="menu-icon">
											<i class="ki-duotone ki-abstract-28 fs-2">
												<span class="path1"></span>
												<span class="path2"></span>
											</i>
										</span>
										<span class="menu-title">User Management</span>
										<span class="menu-arrow"></span>
									</span>
									<!--end:Menu link-->
									<!--begin:Menu sub-->
									<div class="menu-sub menu-sub-accordion">
										<div class="menu-item"><a class="menu-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}"><span class="menu-bullet"><span class="bullet bullet-dot"></span></span><span class="menu-title">Users List</span></a></div>
										<div class="menu-item"><a class="menu-link {{ request()->routeIs('roles.*') ? 'active' : '' }}" href="{{ route('roles.index') }}"><span class="menu-bullet"><span class="bullet bullet-dot"></span></span><span class="menu-title">Roles & Permissions</span></a></div>
									</div>
									<!--end:Menu sub-->
								</div>
								<!--end:Menu item-->
								@endcan

								@if(!auth()->user()->hasRole('Siswa'))
								<!--begin:Menu item-->
								<div class="menu-item pt-5">
									<!--begin:Menu content-->
									<div class="menu-content">
										<span class="menu-heading fw-bold text-uppercase fs-7">System</span>
									</div>
									<!--end:Menu content-->
								</div>
								<!--end:Menu item-->

								<!--begin:Menu item-->
								<div class="menu-item">
									<!--begin:Menu link-->
									<a class="menu-link {{ request()->routeIs('settings.index') ? 'active' : '' }}" href="{{ route('settings.index') }}">
										<span class="menu-icon">
											<i class="ki-duotone ki-setting-2 fs-2">
												<span class="path1"></span>
												<span class="path2"></span>
											</i>
										</span>
										<span class="menu-title">Settings</span>
									</a>
									<!--end:Menu link-->
								</div>
								<!--end:Menu item-->
								@endif

								@can('view_help')
								<!--begin:Menu item-->
								<div class="menu-item">
									<!--begin:Menu link-->
									<a class="menu-link {{ request()->routeIs('log-activity.index') ? 'active' : '' }}" href="{{ route('log-activity.index') }}">
										<span class="menu-icon">
											<i class="ki-duotone ki-message-text-2 fs-2">
												<span class="path1"></span>
												<span class="path2"></span>
												<span class="path3"></span>
											</i>
										</span>
										<span class="menu-title">Log Activity</span>
									</a>
									<!--end:Menu link-->
								</div>
								<!--end:Menu item-->
								@endcan

							</div>
							<!--end::Menu-->
						</div>
						<!--end::Aside Menu-->
					</div>
					<!--end::Aside menu-->

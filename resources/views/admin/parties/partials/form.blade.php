@php
    $isEdit = $party->exists;
@endphp

@push('styles')
<style>
    .wizard-shell { background:#fff; border-radius:16px; box-shadow:0 4px 20px rgba(124,58,237,.08); overflow:hidden; }
    .wizard-steps { display:grid; grid-template-columns:repeat(4,1fr); border-bottom:1px solid #F0EAF8; }
    .wizard-step { padding:16px; font-weight:700; font-size:13px; color:#7A7194; cursor:pointer; border-right:1px solid #F0EAF8; }
    .wizard-step:last-child { border-right:0; }
    .wizard-step.active { color:#fff; background:linear-gradient(135deg,#7C3AED,#5B21B6); }
    .wizard-step span { display:inline-flex; width:26px; height:26px; align-items:center; justify-content:center; border-radius:50%; margin-right:8px; background:rgba(124,58,237,.12); color:#7C3AED; }
    .wizard-step.active span { background:#fff; color:#7C3AED; }
    .wizard-pane { display:none; padding:24px; }
    .wizard-pane.active { display:block; }
    .form-section-title { font-size:12px; font-weight:800; color:#7C3AED; text-transform:uppercase; letter-spacing:.8px; margin:0 0 14px; }
    .balance-preview { background:linear-gradient(135deg,#101827,#251A4E); color:#fff; border-radius:14px; padding:18px; min-height:132px; }
    .balance-preview .amount { font-size:28px; font-weight:800; }
    .balance-preview .label { color:rgba(255,255,255,.72); font-size:12px; }
    @media (max-width: 767px) {
        .wizard-steps { grid-template-columns:1fr; }
        .wizard-step { border-right:0; border-bottom:1px solid #F0EAF8; }
    }
</style>
@endpush

<div class="wizard-shell">
    <div class="wizard-steps">
        <div class="wizard-step active" data-step="1"><span>1</span> Identity</div>
        <div class="wizard-step" data-step="2"><span>2</span> Tax & Address</div>
        <div class="wizard-step" data-step="3"><span>3</span> Balance & Credit</div>
        <div class="wizard-step" data-step="4"><span>4</span> Bank & Notes</div>
    </div>

    <form method="POST" action="{{ $isEdit ? route('admin.parties.update', $party) : route('admin.parties.store') }}" id="partyForm">
        @csrf
        @if($isEdit) @method('PUT') @endif

        <div class="wizard-pane active" data-pane="1">
            <p class="form-section-title">Party identity</p>
            <div class="row">
                <div class="col-md-3 form-group">
                    <label>Party Code *</label>
                    <input name="party_code" class="form-control" value="{{ old('party_code', $party->party_code) }}" required>
                    @error('party_code') <small class="text-danger">{{ $message }}</small> @enderror
                </div>
                <div class="col-md-3 form-group">
                    <label>Party Type *</label>
                    <select name="party_type" class="form-control" required>
                        @foreach(['both'=>'Customer + Supplier','customer'=>'Customer','supplier'=>'Supplier'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('party_type', $party->party_type) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 form-group">
                    <label>Status *</label>
                    <select name="status" class="form-control" required>
                        @foreach(['active'=>'Active','inactive'=>'Inactive','blocked'=>'Blocked'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $party->status) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 form-group">
                    <label>Contact Person</label>
                    <input name="contact_person" class="form-control" value="{{ old('contact_person', $party->contact_person) }}">
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 form-group">
                    <label>Display Name *</label>
                    <input name="display_name" class="form-control" value="{{ old('display_name', $party->display_name) }}" required>
                    @error('display_name') <small class="text-danger">{{ $message }}</small> @enderror
                </div>
                <div class="col-md-6 form-group">
                    <label>Legal / Trade Name</label>
                    <input name="legal_name" class="form-control" value="{{ old('legal_name', $party->legal_name) }}">
                </div>
            </div>
            <div class="row">
                <div class="col-md-3 form-group"><label>Phone</label><input name="phone" class="form-control" value="{{ old('phone', $party->phone) }}"></div>
                <div class="col-md-3 form-group"><label>Alternate Phone</label><input name="alternate_phone" class="form-control" value="{{ old('alternate_phone', $party->alternate_phone) }}"></div>
                <div class="col-md-3 form-group"><label>WhatsApp</label><input name="whatsapp_number" class="form-control" value="{{ old('whatsapp_number', $party->whatsapp_number) }}"></div>
                <div class="col-md-3 form-group"><label>Email</label><input type="email" name="email" class="form-control" value="{{ old('email', $party->email) }}"></div>
            </div>
        </div>

        <div class="wizard-pane" data-pane="2">
            <p class="form-section-title">GST, legal and address details</p>
            <div class="row">
                <div class="col-md-3 form-group">
                    <label>Tax Type *</label>
                    <select name="tax_type" class="form-control" required>
                        @foreach(['registered'=>'Registered','composition'=>'Composition','unregistered'=>'Unregistered','consumer'=>'Consumer','overseas'=>'Overseas'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('tax_type', $party->tax_type) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 form-group"><label>GSTIN</label><input name="gstin" class="form-control text-uppercase" value="{{ old('gstin', $party->gstin) }}"></div>
                <div class="col-md-2 form-group"><label>PAN</label><input name="pan_number" class="form-control text-uppercase" value="{{ old('pan_number', $party->pan_number) }}"></div>
                <div class="col-md-2 form-group"><label>TAN</label><input name="tan_number" class="form-control text-uppercase" value="{{ old('tan_number', $party->tan_number) }}"></div>
                <div class="col-md-2 form-group"><label>CIN</label><input name="cin_number" class="form-control text-uppercase" value="{{ old('cin_number', $party->cin_number) }}"></div>
            </div>
            <div class="row">
                <div class="col-md-4 form-group"><label>Place of Supply</label><input name="place_of_supply" class="form-control" value="{{ old('place_of_supply', $party->place_of_supply) }}"></div>
                <div class="col-md-2 form-group">
                    <label>Country</label>
                    <select name="country" id="countrySelect" class="form-control" data-current="{{ old('country', $party->country ?? 'India') }}"></select>
                </div>
                <div class="col-md-2 form-group">
                    <label>State</label>
                    <select name="state" id="stateSelect" class="form-control" data-current="{{ old('state', $party->state) }}"></select>
                </div>
                <div class="col-md-2 form-group">
                    <label>City</label>
                    <select name="city" id="citySelect" class="form-control" data-current="{{ old('city', $party->city) }}"></select>
                </div>
                <div class="col-md-2 form-group">
                    <label>Pincode</label>
                    <input name="pincode" id="pincodeInput" class="form-control" value="{{ old('pincode', $party->pincode) }}" readonly>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 form-group"><label>Billing Address</label><textarea name="billing_address" class="form-control" rows="4">{{ old('billing_address', $party->billing_address) }}</textarea></div>
                <div class="col-md-6 form-group"><label>Shipping Address</label><textarea name="shipping_address" class="form-control" rows="4">{{ old('shipping_address', $party->shipping_address) }}</textarea></div>
            </div>
        </div>

        <div class="wizard-pane" data-pane="3">
            <p class="form-section-title">Opening balance and credit control</p>
            <div class="row">
                <div class="col-lg-8">
                    <div class="row">
                        <div class="col-md-4 form-group"><label>Opening Balance</label><input type="number" step="0.01" min="0" name="opening_balance" id="opening_balance" class="form-control" value="{{ old('opening_balance', $party->opening_balance ?? 0) }}"></div>
                        <div class="col-md-4 form-group">
                            <label>Balance Nature *</label>
                            <select name="opening_balance_type" id="opening_balance_type" class="form-control" required>
                                <option value="payable" @selected(old('opening_balance_type', $party->opening_balance_type) === 'payable')>We Pay Party</option>
                                <option value="receivable" @selected(old('opening_balance_type', $party->opening_balance_type) === 'receivable')>Party Pays Us</option>
                            </select>
                        </div>
                        <div class="col-md-4 form-group"><label>Opening Balance Date</label><input type="date" name="opening_balance_date" class="form-control" value="{{ old('opening_balance_date', optional($party->opening_balance_date)->format('Y-m-d') ?? now()->toDateString()) }}"></div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 form-group"><label>Credit Limit</label><input type="number" step="0.01" min="0" name="credit_limit" class="form-control" value="{{ old('credit_limit', $party->credit_limit) }}"></div>
                        <div class="col-md-4 form-group"><label>Credit Days</label><input type="number" min="0" name="credit_days" class="form-control" value="{{ old('credit_days', $party->credit_days) }}"></div>
                        <div class="col-md-4 form-group"><label>Payment Terms</label><input name="payment_terms" class="form-control" value="{{ old('payment_terms', $party->payment_terms) }}" placeholder="Net 30, Advance, COD"></div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="balance-preview">
                        <div class="label">Current balance will start as</div>
                        <div class="amount" id="balancePreview">₹ 0.00</div>
                        <div class="label mt-2" id="balanceMeaning">No opening dues</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="wizard-pane" data-pane="4">
            <p class="form-section-title">Banking and internal notes</p>
            <div class="row">
                <div class="col-md-4 form-group"><label>Bank Name</label><input name="bank_name" class="form-control" value="{{ old('bank_name', $party->bank_name) }}"></div>
                <div class="col-md-4 form-group"><label>Account Holder</label><input name="account_holder_name" class="form-control" value="{{ old('account_holder_name', $party->account_holder_name) }}"></div>
                <div class="col-md-4 form-group"><label>Account Number</label><input name="account_number" class="form-control" value="{{ old('account_number', $party->account_number) }}"></div>
            </div>
            <div class="row">
                <div class="col-md-4 form-group"><label>IFSC Code</label><input name="ifsc_code" class="form-control text-uppercase" value="{{ old('ifsc_code', $party->ifsc_code) }}"></div>
                <div class="col-md-4 form-group"><label>Branch</label><input name="branch_name" class="form-control" value="{{ old('branch_name', $party->branch_name) }}"></div>
                <div class="col-md-4 form-group"><label>UPI ID</label><input name="upi_id" class="form-control" value="{{ old('upi_id', $party->upi_id) }}"></div>
            </div>
            <div class="form-group"><label>Notes</label><textarea name="notes" class="form-control" rows="4">{{ old('notes', $party->notes) }}</textarea></div>
            @include('admin.partials.entry-visibility', ['entry' => $party])
        </div>

        <div class="d-flex justify-content-between align-items-center p-4" style="border-top:1px solid #F0EAF8;">
            <button type="button" class="btn btn-light" id="prevStep"><i class="fas fa-arrow-left mr-1"></i> Back</button>
            <div>
                <a href="{{ route('admin.parties.index') }}" class="btn btn-outline-secondary mr-2">Cancel</a>
                <button type="button" class="btn btn-primary" id="nextStep">Next <i class="fas fa-arrow-right ml-1"></i></button>
                <button type="submit" class="btn btn-primary d-none" id="saveParty"><i class="fas fa-save mr-1"></i> Save Party</button>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
let step = 1;
const maxStep = 4;

// ✅ FIX: Sabhi multi-word keys properly quoted kiye gaye hain
const LOCATION_DATA = {
    'India': {
        'Andhra Pradesh': {
            'Visakhapatnam': '530001', 'Vijayawada': '520001', 'Guntur': '522001',
            'Nellore': '524001', 'Kurnool': '518001', 'Rajahmundry': '533101',
            'Kadapa': '516001', 'Kakinada': '533001', 'Tirupati': '517501',
            'Anantapur': '515001', 'Vizianagaram': '535001', 'Eluru': '534001',
            'Ongole': '523001', 'Nandyal': '518501', 'Machilipatnam': '521001',
            'Adoni': '518301', 'Tenali': '522201', 'Chittoor': '517001',
            'Hindupur': '515201', 'Proddatur': '516360', 'Bhimavaram': '534201',
            'Madanapalle': '517325', 'Guntakal': '515801', 'Dharmavaram': '515671',
            'Gudivada': '521301', 'Narasaraopet': '522601', 'Tadipatri': '515411',
            'Amaravati': '522020'
        },
        'Arunachal Pradesh': {
            'Itanagar': '791111', 'Naharlagun': '791110', 'Pasighat': '791102',
            'Bomdila': '790001', 'Ziro': '791120', 'Along': '791001',
            'Changlang': '792120', 'Tezu': '792001', 'Khonsa': '792101',
            'Roing': '792110', 'Daporijo': '791122', 'Tawang': '790104',
            'Seppa': '791102', 'Yingkiong': '791001'
        },
        'Assam': {
            'Guwahati': '781001', 'Silchar': '788001', 'Dibrugarh': '786001',
            'Jorhat': '785001', 'Nagaon': '782001', 'Tinsukia': '786125',
            'Tezpur': '784001', 'Bongaigaon': '783380', 'Dhubri': '783301',
            'Diphu': '782460', 'North Lakhimpur': '787001', 'Sivasagar': '785640',
            'Goalpara': '783101', 'Barpeta': '781301', 'Karimganj': '788710',
            'Hailakandi': '788151', 'Golaghat': '785621', 'Morigaon': '782105',
            'Nalbari': '781335', 'Kokrajhar': '783370', 'Haflong': '788819'
        },
        'Bihar': {
            'Patna': '800001', 'Gaya': '823001', 'Muzaffarpur': '842001',
            'Bhagalpur': '812001', 'Darbhanga': '846001', 'Arrah': '802301',
            'Begusarai': '851101', 'Chhapra': '841301', 'Katihar': '854105',
            'Munger': '811201', 'Purnia': '854301', 'Saharsa': '852201',
            'Hajipur': '844101', 'Sasaram': '821115', 'Dehri': '821307',
            'Motihari': '845401', 'Bettiah': '845438', 'Sitamarhi': '843301',
            'Nawada': '805110', 'Aurangabad': '824101', 'Jehanabad': '804408',
            'Buxar': '802101', 'Kishanganj': '855107', 'Madhepura': '852113',
            'Supaul': '852131', 'Araria': '854311', 'Siwan': '841226',
            'Gopalganj': '841428', 'Sheohar': '843329', 'Madhubani': '847211',
            'Vaishali': '844128', 'Nalanda': '803101', 'Rohtas': '821115',
            'Kaimur': '821101', 'Lakhisarai': '811311', 'Sheikhpura': '811105',
            'Khagaria': '851204', 'Jamui': '811307', 'Banka': '813102'
        },
        'Chhattisgarh': {
            'Raipur': '492001', 'Bhilai': '490001', 'Bilaspur': '495001',
            'Korba': '495677', 'Durg': '491001', 'Rajnandgaon': '491441',
            'Jagdalpur': '494001', 'Raigarh': '496001', 'Ambikapur': '497001',
            'Dhamtari': '493773', 'Mahasamund': '493445', 'Kanker': '494334',
            'Kondagaon': '494226', 'Narayanpur': '494661', 'Bijapur': '494444',
            'Sukma': '494111', 'Kawardha': '491995', 'Balod': '491226',
            'Balodabazar': '493332', 'Gariaband': '493889', 'Mungeli': '495334',
            'Surajpur': '497229', 'Balrampur': '497119', 'Jashpur': '496331'
        },
        'Goa': {
            'Panaji': '403001', 'Margao': '403601', 'Vasco da Gama': '403802',
            'Mapusa': '403507', 'Ponda': '403401', 'Bicholim': '403504',
            'Curchorem': '403706', 'Cuncolim': '403703', 'Quepem': '403705',
            'Canacona': '403702', 'Pernem': '403512', 'Calangute': '403516',
            'Candolim': '403515', 'Colva': '403708'
        },
        'Gujarat': {
            'Ahmedabad': '380001', 'Surat': '395003', 'Vadodara': '390001',
            'Rajkot': '360001', 'Bhavnagar': '364001', 'Jamnagar': '361001',
            'Junagadh': '362001', 'Gandhinagar': '382010', 'Anand': '388001',
            'Navsari': '396445', 'Morbi': '363641', 'Nadiad': '387001',
            'Surendranagar': '363001', 'Bharuch': '392001', 'Mehsana': '384001',
            'Bhuj': '370001', 'Porbandar': '360575', 'Palanpur': '385001',
            'Valsad': '396001', 'Vapi': '396195', 'Gondal': '360311',
            'Veraval': '362265', 'Godhra': '389001', 'Patan': '384265',
            'Dahod': '389151', 'Botad': '364710', 'Amreli': '365601',
            'Ankleshwar': '393001', 'Deesa': '385535', 'Modasa': '383315',
            'Himatnagar': '383001', 'Kapadvanj': '387620', 'Jetpur': '360370',
            'Wankaner': '363621', 'Dwarka': '361335', 'Khambhat': '388620'
        },
        'Haryana': {
            'Gurugram': '122001', 'Faridabad': '121001', 'Panipat': '132103',
            'Ambala': '134003', 'Yamunanagar': '135001', 'Rohtak': '124001',
            'Hisar': '125001', 'Karnal': '132001', 'Sonipat': '131001',
            'Panchkula': '134109', 'Bhiwani': '127021', 'Sirsa': '125055',
            'Bahadurgarh': '124507', 'Rewari': '123401', 'Kaithal': '136027',
            'Kurukshetra': '136118', 'Jind': '126102', 'Thanesar': '136118',
            'Palwal': '121102', 'Pinjore': '134102', 'Narnaul': '123001',
            'Nuh': '122107', 'Fatehabad': '125050', 'Mahendragarh': '123029',
            'Jhajjar': '124103', 'Charkhi Dadri': '127306'
        },
        'Himachal Pradesh': {
            'Shimla': '171001', 'Mandi': '175001', 'Solan': '173212',
            'Dharamshala': '176215', 'Bilaspur': '174001', 'Chamba': '176310',
            'Kullu': '175101', 'Hamirpur': '177001', 'Una': '174303',
            'Nahan': '173001', 'Sundernagar': '175018', 'Palampur': '176061',
            'Baddi': '173205', 'Paonta Sahib': '173025', 'Kangra': '176001',
            'Nurpur': '176202', 'Keylong': '175130', 'Rampur': '172001',
            'Rohru': '171207', 'Kaza': '172114', 'Reckong Peo': '172107',
            'Narkanda': '171213'
        },
        'Jharkhand': {
            'Ranchi': '834001', 'Jamshedpur': '831001', 'Dhanbad': '826001',
            'Bokaro': '827001', 'Deoghar': '814112', 'Hazaribagh': '825301',
            'Giridih': '815301', 'Ramgarh': '829122', 'Medininagar': '822101',
            'Chatra': '825401', 'Simdega': '835223', 'Pakur': '816107',
            'Dumka': '814101', 'Godda': '814133', 'Sahibganj': '816109',
            'Koderma': '825410', 'Latehar': '829206', 'Garhwa': '822114',
            'Palamu': '822101', 'Lohardaga': '835302', 'Gumla': '835207',
            'Khunti': '835210', 'Chaibasa': '833201', 'Saraikela': '832401'
        },
        'Karnataka': {
            'Bengaluru': '560001', 'Mysuru': '570001', 'Mangaluru': '575001',
            'Hubli': '580001', 'Dharwad': '580001', 'Belagavi': '590001',
            'Kalaburagi': '585101', 'Davanagere': '577001', 'Ballari': '583101',
            'Vijayapura': '586101', 'Shivamogga': '577201', 'Tumkur': '572101',
            'Raichur': '584101', 'Bidar': '585401', 'Hassan': '573201',
            'Udupi': '576101', 'Chitradurga': '577501', 'Chikkamagaluru': '577101',
            'Bagalkot': '587101', 'Gadag': '582101', 'Koppal': '583231',
            'Yadgir': '585201', 'Chikkaballapur': '562101', 'Kolar': '563101',
            'Mandya': '571401', 'Ramanagara': '562159', 'Chamarajanagar': '571313',
            'Kodagu': '571201', 'Karwar': '581301', 'Sirsi': '581401',
            'Puttur': '574201'
        },
        'Kerala': {
            'Thiruvananthapuram': '695001', 'Kochi': '682001', 'Kozhikode': '673001',
            'Thrissur': '680001', 'Kollam': '691001', 'Kannur': '670001',
            'Alappuzha': '688001', 'Palakkad': '678001', 'Kottayam': '686001',
            'Malappuram': '676505', 'Kasaragod': '671121', 'Pathanamthitta': '689645',
            'Idukki': '685602', 'Wayanad': '673121', 'Ernakulam': '682011',
            'Munnar': '685612', 'Varkala': '695141', 'Ponnani': '679577',
            'Tirur': '676101', 'Thalassery': '670101', 'Vatakara': '673104',
            'Kalpetta': '673121', 'Manjeri': '676121', 'Perinthalmanna': '679322',
            'Ottappalam': '679101', 'Cherthala': '688524', 'Kayamkulam': '690502',
            'Changanassery': '686101', 'Tiruvalla': '689101', 'Pala': '686575',
            'Adoor': '691523', 'Punalur': '691305', 'Attingal': '695101',
            'Neyyattinkara': '695121'
        },
        'Madhya Pradesh': {
            'Bhopal': '462001', 'Indore': '452001', 'Jabalpur': '482001',
            'Gwalior': '474001', 'Ujjain': '456001', 'Sagar': '470001',
            'Dewas': '455001', 'Satna': '485001', 'Ratlam': '457001',
            'Rewa': '486001', 'Murwara': '483501', 'Singrauli': '486889',
            'Burhanpur': '450331', 'Khandwa': '450001', 'Bhind': '477001',
            'Chhindwara': '480001', 'Guna': '473001', 'Shivpuri': '473551',
            'Vidisha': '464001', 'Chhatarpur': '471001', 'Damoh': '470661',
            'Mandsaur': '458001', 'Khargone': '451001', 'Neemuch': '458441',
            'Pithampur': '454775', 'Narmadapuram': '461001', 'Itarsi': '461111',
            'Sehore': '466001', 'Betul': '460001', 'Morena': '476001',
            'Shahdol': '484001', 'Seoni': '480661', 'Narsinghpur': '487001',
            'Tikamgarh': '472001', 'Panna': '488001', 'Umaria': '484661',
            'Dindori': '481880', 'Balaghat': '481001', 'Mandla': '481661',
            'Rajgarh': '465661', 'Shajapur': '465001', 'Barwani': '451551',
            'Alirajpur': '457887', 'Jhabua': '457661', 'Dhar': '454001',
            'Agar Malwa': '465441'
        },
        'Maharashtra': {
            'Mumbai': '400001', 'Pune': '411001', 'Nagpur': '440001',
            'Nashik': '422001', 'Thane': '400601', 'Aurangabad': '431001',
            'Solapur': '413001', 'Kolhapur': '416001', 'Amravati': '444601',
            'Nanded': '431601', 'Sangli': '416416', 'Malegaon': '423203',
            'Jalgaon': '425001', 'Akola': '444001', 'Latur': '413512',
            'Dhule': '424001', 'Ahmednagar': '414001', 'Chandrapur': '442401',
            'Parbhani': '431401', 'Ichalkaranji': '416115', 'Jalna': '431203',
            'Bhiwandi': '421302', 'Navi Mumbai': '400703', 'Panvel': '410206',
            'Ulhasnagar': '421003', 'Satara': '415001', 'Osmanabad': '413501',
            'Beed': '431122', 'Hingoli': '431513', 'Washim': '444505',
            'Buldhana': '443001', 'Yavatmal': '445001', 'Wardha': '442001',
            'Gondia': '441601', 'Bhandara': '441904', 'Gadchiroli': '442605',
            'Sindhudurg': '416812', 'Ratnagiri': '415612', 'Raigad': '402201',
            'Alibag': '402201', 'Karjat': '410201', 'Wai': '412803',
            'Karad': '415110', 'Baramati': '413102', 'Shirdi': '423109',
            'Kopargaon': '423601', 'Ambernath': '421501'
        },
        'Manipur': {
            'Imphal': '795001', 'Thoubal': '795138', 'Bishnupur': '795126',
            'Churachandpur': '795128', 'Senapati': '795106', 'Ukhrul': '795142',
            'Tamenglong': '795159', 'Chandel': '795107', 'Jiribam': '795008',
            'Kangpokpi': '795129', 'Noney': '795145', 'Pherzawl': '795009',
            'Tengnoupal': '795140'
        },
        'Meghalaya': {
            'Shillong': '793001', 'Tura': '794001', 'Jowai': '793150',
            'Nongstoin': '793119', 'Williamnagar': '794111', 'Baghmara': '794102',
            'Resubelpara': '794114', 'Mairang': '793109', 'Nongpoh': '793105',
            'Cherrapunji': '793108', 'Mawkyrwat': '793122', 'Ampati': '794109'
        },
        'Mizoram': {
            'Aizawl': '796001', 'Lunglei': '796701', 'Saiha': '796901',
            'Champhai': '796321', 'Kolasib': '796081', 'Serchhip': '796181',
            'Lawngtlai': '796891', 'Mamit': '796441', 'Saitual': '796025',
            'Khawzawl': '796411', 'Hnahthial': '796770'
        },
        'Nagaland': {
            'Kohima': '797001', 'Dimapur': '797112', 'Mokokchung': '798601',
            'Tuensang': '798612', 'Wokha': '797111', 'Zunheboto': '798620',
            'Phek': '797108', 'Mon': '798621', 'Kiphire': '798615',
            'Longleng': '798618', 'Peren': '797110', 'Noklak': '798619'
        },
        'Odisha': {
            'Bhubaneswar': '751001', 'Cuttack': '753001', 'Rourkela': '769001',
            'Brahmapur': '760001', 'Sambalpur': '768001', 'Puri': '752001',
            'Balasore': '756001', 'Bhadrak': '756100', 'Baripada': '757001',
            'Jharsuguda': '768201', 'Jeypore': '764001', 'Bargarh': '768028',
            'Kendujhar': '758001', 'Sundargarh': '770001', 'Koraput': '764020',
            'Rayagada': '765001', 'Angul': '759001', 'Dhenkanal': '759001',
            'Phulbani': '762001', 'Bolangir': '767001', 'Sonepur': '767017',
            'Nuapada': '766105', 'Titlagarh': '767033', 'Nawapara': '766001',
            'Malkangiri': '764045', 'Nabarangpur': '764059', 'Gajapati': '761200',
            'Nayagarh': '752069', 'Jagatsinghapur': '754103', 'Kendrapara': '754211',
            'Jajpur': '755001', 'Khordha': '752056'
        },
        'Punjab': {
            'Ludhiana': '141001', 'Amritsar': '143001', 'Jalandhar': '144001',
            'Patiala': '147001', 'Bathinda': '151001', 'Hoshiarpur': '146001',
            'Mohali': '160055', 'Firozpur': '152001', 'Pathankot': '145001',
            'Moga': '142001', 'Abohar': '152116', 'Batala': '143505',
            'Gurdaspur': '143521', 'Phagwara': '144401', 'Muktsar': '152026',
            'Barnala': '148101', 'Rajpura': '140401', 'Kapurthala': '144601',
            'Sangrur': '148001', 'Rupnagar': '140001', 'Nawanshahr': '144514',
            'Fatehgarh Sahib': '140407', 'Mansa': '151505', 'Tarn Taran': '143401',
            'Khanna': '141401', 'Malerkotla': '148023', 'Faridkot': '151203',
            'Zirakpur': '140603'
        },
        'Rajasthan': {
            'Jaipur': '302001', 'Jodhpur': '342001', 'Udaipur': '313001',
            'Kota': '324001', 'Bikaner': '334001', 'Ajmer': '305001',
            'Bhilwara': '311001', 'Alwar': '301001', 'Bharatpur': '321001',
            'Sikar': '332001', 'Pali': '306401', 'Sri Ganganagar': '335001',
            'Hanumangarh': '335512', 'Jhunjhunu': '333001', 'Tonk': '304001',
            'Dausa': '303303', 'Baran': '325205', 'Barmer': '344001',
            'Jaisalmer': '345001', 'Nagaur': '341001', 'Churu': '331001',
            'Dungarpur': '314001', 'Banswara': '327001', 'Sawai Madhopur': '322001',
            'Sirohi': '307001', 'Jhalawar': '326001', 'Rajsamand': '313324',
            'Bundi': '323001', 'Chittorgarh': '312001', 'Dholpur': '328001',
            'Karauli': '322241', 'Pratapgarh': '312605', 'Jalore': '343001',
            'Kishangarh': '305801', 'Beawar': '305901', 'Makrana': '341505'
        },
        'Sikkim': {
            'Gangtok': '737101', 'Namchi': '737126', 'Gyalshing': '737111',
            'Mangan': '737116', 'Rangpo': '737132', 'Jorethang': '737121',
            'Ravangla': '737139', 'Yuksom': '737113', 'Pelling': '737113'
        },
        'Tamil Nadu': {
            'Chennai': '600001', 'Coimbatore': '641001', 'Madurai': '625001',
            'Tiruchirappalli': '620001', 'Salem': '636001', 'Tirunelveli': '627001',
            'Tiruppur': '641601', 'Vellore': '632001', 'Erode': '638001',
            'Thoothukudi': '628001', 'Dindigul': '624001', 'Thanjavur': '613001',
            'Ranipet': '632401', 'Sivakasi': '626123', 'Karur': '639001',
            'Udhagamandalam': '643001', 'Hosur': '635109', 'Nagercoil': '629001',
            'Kanchipuram': '631501', 'Karaikkudi': '630001', 'Neyveli': '607803',
            'Cuddalore': '607001', 'Kumbakonam': '612001', 'Nagapattinam': '611001',
            'Viluppuram': '605602', 'Tiruvannamalai': '606601', 'Pollachi': '642001',
            'Rajapalayam': '626117', 'Gudiyatham': '632602', 'Pudukkottai': '622001',
            'Palayamkottai': '627002', 'Mettur': '636401', 'Mayiladuthurai': '609001',
            'Ariyalur': '621704', 'Perambalur': '621212', 'Dharmapuri': '636701',
            'Krishnagiri': '635001', 'Namakkal': '637001', 'Nilgiris': '643001',
            'Thiruvarur': '610001', 'Tenkasi': '627811', 'Virudhunagar': '626001',
            'Kallakurichi': '606202'
        },
        'Telangana': {
            'Hyderabad': '500001', 'Warangal': '506002', 'Nizamabad': '503001',
            'Karimnagar': '505001', 'Khammam': '507001', 'Nalgonda': '508001',
            'Mahbubnagar': '509001', 'Ramagundam': '505208', 'Adilabad': '504001',
            'Siddipet': '502103', 'Miryalaguda': '508207', 'Sangareddy': '502001',
            'Mancherial': '504208', 'Nirmal': '504106', 'Jagtial': '505327',
            'Suryapet': '508213', 'Wanaparthy': '509103', 'Bhupalpally': '506169',
            'Jangaon': '506167', 'Yadadri': '508115', 'Vikarabad': '501101',
            'Medchal': '501401', 'Narayanpet': '509210', 'Gadwal': '509125',
            'Nagar Kurnool': '509209', 'Medak': '502110', 'Kamareddy': '503111',
            'Peddapalli': '505172', 'Bhadradri Kothagudem': '507101', 'Mulugu': '506342'
        },
        'Tripura': {
            'Agartala': '799001', 'Udaipur': '799120', 'Dharmanagar': '799253',
            'Kailashahar': '799277', 'Ambassa': '799102', 'Belonia': '799155',
            'Khowai': '799202', 'Sabroom': '799145', 'Sonamura': '799131',
            'Amarpur': '799101', 'Bishalgarh': '799102', 'Kumarghat': '799264'
        },
        'Uttar Pradesh': {
            'Lucknow': '226001', 'Kanpur': '208001', 'Noida': '201301',
            'Ghaziabad': '201001', 'Agra': '282001', 'Varanasi': '221001',
            'Meerut': '250001', 'Prayagraj': '211001', 'Bareilly': '243001',
            'Aligarh': '202001', 'Moradabad': '244001', 'Saharanpur': '247001',
            'Gorakhpur': '273001', 'Firozabad': '283203', 'Jhansi': '284001',
            'Muzaffarnagar': '251001', 'Mathura': '281001', 'Rampur': '244901',
            'Shahjahanpur': '242001', 'Farrukhabad': '209625', 'Mau': '275101',
            'Hapur': '245101', 'Etawah': '206001', 'Mirzapur': '231001',
            'Bulandshahr': '203001', 'Sambhal': '244302', 'Amroha': '244221',
            'Hardoi': '241001', 'Fatehpur': '212601', 'Raebareli': '229001',
            'Orai': '285001', 'Sitapur': '261001', 'Bahraich': '271801',
            'Unnao': '209801', 'Jaunpur': '222001', 'Lakhimpur': '262701',
            'Hathras': '204101', 'Banda': '210001', 'Pilibhit': '262001',
            'Barabanki': '225001', 'Khurja': '203131', 'Gonda': '271001',
            'Mainpuri': '205001', 'Lalitpur': '284403', 'Etah': '207001',
            'Deoria': '274001', 'Basti': '272001', 'Azamgarh': '276001',
            'Ballia': '277001', 'Sultanpur': '228001', 'Ayodhya': '224123',
            'Greater Noida': '201310', 'Vrindavan': '281121', 'Kasganj': '207123',
            'Budaun': '243601', 'Bijnor': '246701', 'Sonbhadra': '231213',
            'Maharajganj': '273303', 'Siddharthnagar': '272207', 'Kushinagar': '274403',
            'Pratapgarh': '230001', 'Kaushambi': '212201', 'Chitrakoot': '210205',
            'Hamirpur': '210301', 'Mahoba': '210427', 'Kannauj': '209725',
            'Auraiya': '206122', 'Balrampur': '271201', 'Shravasti': '271831',
            'Ambedkar Nagar': '224122', 'Amethi': '227405'
        },
        'Uttarakhand': {
            'Dehradun': '248001', 'Haridwar': '249401', 'Roorkee': '247667',
            'Haldwani': '263139', 'Rudrapur': '263153', 'Kashipur': '244713',
            'Nainital': '263001', 'Pithoragarh': '262501', 'Almora': '263601',
            'Rishikesh': '249201', 'Mussoorie': '248179', 'Kotdwar': '246149',
            'Ramnagar': '244715', 'Bageshwar': '263642', 'Chamoli': '246401',
            'Champawat': '262523', 'Tehri': '249001', 'Uttarkashi': '249193',
            'Lansdowne': '246155', 'Pauri': '246001', 'Srinagar': '246174',
            'Rudraprayag': '246171', 'Gopeshwar': '246401'
        },
        'West Bengal': {
            'Kolkata': '700001', 'Howrah': '711101', 'Siliguri': '734001',
            'Durgapur': '713201', 'Asansol': '713301', 'Barddhaman': '713101',
            'Malda': '732101', 'Baharampur': '742101', 'Jalpaiguri': '735101',
            'Krishnanagar': '741101', 'Medinipur': '721101', 'Kharagpur': '721301',
            'Haldia': '721607', 'Alipurduar': '736121', 'Cooch Behar': '736101',
            'Darjeeling': '734101', 'Bankura': '722101', 'Purulia': '723101',
            'Balurghat': '733101', 'Raiganj': '733134', 'Bolpur': '731204',
            'Shantiniketan': '731235', 'Nabadwip': '741302', 'Ranaghat': '741201',
            'Kalyani': '741235', 'Barrackpore': '700120', 'Dum Dum': '700028',
            'Salt Lake': '700064', 'Rajarhat': '700135', 'Bhatpara': '743123',
            'Habra': '743268', 'Barasat': '700124', 'Basirhat': '743411',
            'Hooghly': '712103', 'Serampore': '712201', 'Chandannagar': '712136',
            'Diamond Harbour': '743331', 'Tamluk': '721636'
        },
        'Delhi': {
            'New Delhi': '110001', 'Central Delhi': '110006', 'North Delhi': '110007',
            'South Delhi': '110017', 'East Delhi': '110032', 'West Delhi': '110058',
            'Dwarka': '110075', 'Rohini': '110085', 'Janakpuri': '110058',
            'Pitampura': '110034', 'Lajpat Nagar': '110024', 'Saket': '110017',
            'Vasant Kunj': '110070', 'Karol Bagh': '110005', 'Connaught Place': '110001',
            'Nehru Place': '110019', 'Preet Vihar': '110092', 'Mayur Vihar': '110091',
            'Shahdara': '110032', 'Uttam Nagar': '110059', 'Rajouri Garden': '110027'
        },
        'Jammu and Kashmir': {
            'Srinagar': '190001', 'Jammu': '180001', 'Anantnag': '192101',
            'Sopore': '193201', 'Baramulla': '193101', 'Udhampur': '182101',
            'Kathua': '184101', 'Rajouri': '185131', 'Poonch': '185101',
            'Kupwara': '193222', 'Kulgam': '192231', 'Pulwama': '192301',
            'Budgam': '191111', 'Bandipora': '193502', 'Ganderbal': '191201',
            'Reasi': '182311', 'Ramban': '182144', 'Doda': '182202',
            'Kishtwar': '182204', 'Samba': '184121', 'Shopian': '192303'
        },
        'Ladakh': {
            'Leh': '194101', 'Kargil': '194103', 'Diskit': '194401',
            'Padum': '194401', 'Zanskar': '194401', 'Nyoma': '194401'
        },
        'Chandigarh': {
            'Chandigarh': '160001', 'Mohali': '160055', 'Panchkula': '134109',
            'Manimajra': '160101', 'Industrial Area': '160002'
        },
        'Puducherry': {
            'Puducherry': '605001', 'Karaikal': '609602', 'Mahe': '673310',
            'Yanam': '533464', 'Villianur': '605110', 'Bahour': '607402',
            'Ariyankuppam': '605007', 'Ozhukarai': '605010'
        },
        'Andaman and Nicobar Islands': {
            'Port Blair': '744101', 'Car Nicobar': '744301', 'Diglipur': '744202',
            'Rangat': '744204', 'Mayabunder': '744203', 'Campbell Bay': '744302',
            'Havelock Island': '744211', 'Neil Island': '744104'
        },
        'Dadra and Nagar Haveli and Daman and Diu': {
            'Silvassa': '396230', 'Daman': '396210', 'Diu': '362520',
            'Amli': '396240', 'Naroli': '396236', 'Khanvel': '396230'
        },
        'Lakshadweep': {
            'Kavaratti': '682555', 'Agatti': '682553', 'Andrott': '682557',
            'Kalpeni': '682556', 'Minicoy': '682559', 'Amini': '682551',
            'Kadmat': '682554'
        }
    },
    'Nepal': {
        'Bagmati': {
            'Kathmandu': '44600',
            'Lalitpur':  '44700',
            'Bhaktapur': '44800'
        }
    },
    'United Arab Emirates': {
        'Dubai': {
            'Dubai': '00000'
        },
        'Abu Dhabi': {
            'Abu Dhabi': '00000',
            'Al Ain':    '00000'
        }
    },
    'United States': {
        'California': {
            'Los Angeles':   '90001',
            'San Francisco': '94102'
        },
        'New York': {
            'New York': '10001',
            'Buffalo':  '14201'
        }
    }
};

function renderStep() {
    $('.wizard-step, .wizard-pane').removeClass('active');
    $(`.wizard-step[data-step="${step}"], .wizard-pane[data-pane="${step}"]`).addClass('active');
    $('#prevStep').prop('disabled', step === 1);
    $('#nextStep').toggleClass('d-none', step === maxStep);
    $('#saveParty').toggleClass('d-none', step !== maxStep);
}

function renderBalance() {
    const amount = parseFloat($('#opening_balance').val() || '0');
    const type   = $('#opening_balance_type').val();
    $('#balancePreview').text('₹ ' + amount.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
    $('#balanceMeaning').text(
        amount <= 0
            ? 'No opening dues'
            : (type === 'payable'
                ? 'Payable: company has to pay this party'
                : 'Receivable: party has to pay company')
    );
}

function fillSelect($select, options, selected, placeholder) {
    const values = Object.keys(options || {});
    let html = `<option value="">${placeholder}</option>` +
        values.map(v => `<option value="${v}"${v === selected ? ' selected' : ''}>${v}</option>`).join('');
    if (selected && !values.includes(selected)) {
        html += `<option value="${selected}" selected>${selected}</option>`;
    }
    $select.html(html);
}

function renderCountries() {
    const current = $('#countrySelect').data('current') || 'India';
    fillSelect($('#countrySelect'), LOCATION_DATA, current, 'Select country');
    renderStates();
}

function renderStates() {
    const country  = $('#countrySelect').val();
    const selected = $('#stateSelect').data('current') || '';
    fillSelect($('#stateSelect'), LOCATION_DATA[country] || {}, selected, 'Select state');
    $('#stateSelect').data('current', '');
    renderCities();
}

function renderCities() {
    const country  = $('#countrySelect').val();
    const state    = $('#stateSelect').val();
    const selected = $('#citySelect').data('current') || '';
    fillSelect($('#citySelect'), (LOCATION_DATA[country] || {})[state] || {}, selected, 'Select city');
    $('#citySelect').data('current', '');
    renderPincode();
}

function renderPincode() {
    const country = $('#countrySelect').val();
    const state   = $('#stateSelect').val();
    const city    = $('#citySelect').val();
    const pincode = (((LOCATION_DATA[country] || {})[state] || {})[city]) || '';
    if (pincode) $('#pincodeInput').val(pincode);
    if (!$('[name="place_of_supply"]').val() && state) {
        $('[name="place_of_supply"]').val(state);
    }
}

$('#nextStep').on('click', function () { if (step < maxStep) { step++; renderStep(); } });
$('#prevStep').on('click', function () { if (step > 1)       { step--; renderStep(); } });
$('.wizard-step').on('click', function () { step = parseInt($(this).data('step')); renderStep(); });
$('#opening_balance, #opening_balance_type').on('input change', renderBalance);
$('#countrySelect').on('change', function () { $('#pincodeInput').val(''); renderStates(); });
$('#stateSelect').on('change',  function () { $('#pincodeInput').val(''); renderCities(); });
$('#citySelect').on('change', renderPincode);

renderStep();
renderBalance();
renderCountries();
</script>
@endpush


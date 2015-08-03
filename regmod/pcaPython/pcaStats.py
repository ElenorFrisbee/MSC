import numpy as np
import statsmodels.api as sm
import warnings

def pca(data, dims_rescaled_data=100):
    """
    modified from: http://stackoverflow.com/a/13224592
    returns: data transformed in 2 dims/columns + regenerated original data
    pass in: data as 2D NumPy array
    """

    from scipy import linalg as la
    m, n = data.shape
    # calculate the covariance matrix
    R = np.cov(data, rowvar=False)
    # calculate eigenvectors & eigenvalues of the covariance matrix
    # use 'eigh' rather than 'eig' since R is symmetric, 
    # the performance gain is substantial
    evals, evecs = la.eigh(R)
    # sort eigenvalue in decreasing order
    idx = np.argsort(evals)[::-1]
    evecs = evecs[:,idx]
    # sort eigenvectors according to same index
    evals = evals[idx]
    # select the first n eigenvectors (n is desired dimension
    # of rescaled data array, or dims_rescaled_data)
    evecs = evecs[:, :dims_rescaled_data]
    # carry out the transformation on the data using eigenvectors
    # and return the re-scaled data, eigenvalues, and eigenvectors
    return evals, evecs

# define mlr function
def mlr(y, x):
    results = sm.OLS(y, x, missing='drop').fit()
    return results

def reconstruct(zscore, regionReprInCol, indicesRecon, mSelCol, cruMapStd):
    # define amaount of relevant hk classes
    n = 20
    a1 = .85

    #calculate pca  allhkas_temp = loadings, eigenvek = eigenvek 
    eigenvek, allhkas_temp = pca(zscore)

    erkl_var = eigenvek[0:n]/sum(eigenvek)

    # changed from < .95
    nx = np.cumsum(erkl_var) < .9
    nx = sum(nx)
    nx = nx

    # prepare hk's matrix 
    allhkas = np.empty((regionReprInCol.shape[0],n,))
    allhkas[:] = np.NAN

    allhkas[~np.isnan(regionReprInCol)[:,0],:] = allhkas_temp[:,0:n]

    allhkas = allhkas[:,0:nx]
    pcs = allhkas.reshape(nx, 81*161)*-1 # for abs its right!

    # prepare mlr
    y = indicesRecon.reshape(81*161,1)*a1
    x = pcs.reshape(81*161,nx)
    x = sm.add_constant(x) # add intercept

    # calculate mlr and coefficients
    coefficients =  mlr(y, x)._results.params

    # returns Na row for X1, only values for no na passed to variables
    coefficients = coefficients[~np.isnan(coefficients)]

    # calculate dot product of coefficients with index
    recunstructed = np.dot(x, coefficients)
    recunstructed = recunstructed.reshape(81,161)

    # get mean cru map for month
    # expect to see RuntimeWarnings in this block => Mean of empty (NaN) slice
    with warnings.catch_warnings():
        warnings.simplefilter("ignore", category=RuntimeWarning)
        cruMapMean = np.nanmean(mSelCol, axis=0)
    cruMapMean = cruMapMean.reshape(81,161)
   
    recunstructed = (recunstructed*cruMapStd)+cruMapMean
    
    return recunstructed 

# modified from: http://stackoverflow.com/a/24448379    
# translate csv to hdf5 for much better performance
#for i in range(1,13):
#    pca_data = np.genfromtxt('/var/shiny-server/www/datacollectorv2/CRU_Data/mSelInCol_'+str(i)+'.csv', delimiter=',', skip_header = 1)
#    h = h5py.File('/var/shiny-server/www/datacollectorv2/CRU_Data/mSelInCol_'+str(i)+'.hdf5', 'w')
#    dset = h.create_dataset('cru_data', data=pca_data)
#    print i
